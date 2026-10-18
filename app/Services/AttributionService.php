<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\ConversionStatus;
use App\Models\AttributionClick;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\Conversion;
use App\Services\Fraud\FraudDetectionService;
use App\Services\Math\MoneyMath;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributionService
{
    /**
     * Cookie name for privacy-conscious first-party visitor identification
     */
    public const VISITOR_COOKIE = 'nmw_vid';

    public function __construct(
        protected ?FraudDetectionService $fraudService = null
    ) {
        $this->fraudService = $this->fraudService ?: app(FraudDetectionService::class);
    }

    /**
     * Record a click event and prepare attribution session
     *
     * @return array{
     *   success: bool,
     *   click?: AttributionClick,
     *   campaign?: Campaign,
     *   application?: CampaignApplication,
     *   destination_url?: string,
     *   visitor_id?: string,
     *   cookie_days?: int,
     *   error?: string,
     *   message?: string
     * }
     */
    public function recordClick(Request $request, string $trackingCode): array
    {
        $normalizedCode = Str::upper(trim($trackingCode));

        // 1. Locate approved campaign application by tracking code
        $application = CampaignApplication::with(['campaign.brandProfile', 'marketerProfile'])
            ->whereRaw('UPPER(tracking_code) = ?', [$normalizedCode])
            ->where('status', CampaignApplicationStatus::APPROVED)
            ->first();

        if (!$application) {
            return [
                'success' => false,
                'error' => 'invalid_tracking_code',
                'message' => 'رمز التتبع غير صحيح أو غير معتمد من قِبل العلامة التجارية.',
            ];
        }

        $campaign = $application->campaign;

        // 2. Validate campaign is currently active
        if (!$campaign || $campaign->status !== CampaignStatus::ACTIVE) {
            return [
                'success' => false,
                'error' => 'campaign_not_active',
                'message' => 'هذه الحملة التسويقية غير نشطة أو متوقفة حالياً.',
            ];
        }

        // 3. Visitor Identification (Privacy-conscious first-party token)
        $cookieVisitorId = $request->cookie(self::VISITOR_COOKIE);
        if ($cookieVisitorId && preg_match('/^[A-Za-z0-9_\-]{16,64}$/', $cookieVisitorId)) {
            $visitorId = $cookieVisitorId;
        } else {
            $visitorId = 'vid_' . Str::random(32);
        }

        // 4. Session Identification
        $sessionId = $request->hasSession() ? ($request->session()->getId() ?: ('sid_' . Str::random(32))) : ('sid_' . Str::random(32));

        // 5. Privacy-preserving hashing of IP and User-Agent
        $clientIp = $request->ip() ?: '127.0.0.1';
        $ipSalt = config('app.key') ?: 'nomowly_secure_attribution_salt';
        $ipHash = hash('sha256', $clientIp . $ipSalt);

        $userAgent = $request->userAgent() ?: 'Unknown';
        $userAgentHash = hash('sha256', $userAgent);

        // 6. Device Type Detection
        $deviceType = $this->detectDeviceType($userAgent);

        // 7. Destination Resolution
        $destinationUrl = $campaign->destination_url;
        if (empty($destinationUrl)) {
            $destinationUrl = route('marketplace.show', $campaign->slug);
        }

        // 8. Resolve Terms Version and Lock Campaign Terms
        $termsVersionId = $application->terms_version_id ?: $campaign->current_terms_version_id;
        $campaign->lockTerms('تم تثبيت الشروط التعاقدية للحملة تلقائياً لتسجيل أول نقرة إسناد ترويجية.');

        // 9. Record Attribution Click Entity
        $click = AttributionClick::create([
            'campaign_id' => $campaign->id,
            'campaign_application_id' => $application->id,
            'terms_version_id' => $termsVersionId,
            'tracking_code' => $application->tracking_code,
            'tracking_link' => $application->tracking_link,
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'referrer' => Str::limit($request->headers->get('referer'), 500, ''),
            'landing_url' => Str::limit($destinationUrl, 500, ''),
            'country' => $request->header('CF-IPCountry') ?: ($request->header('X-Country-Code') ?: 'SA'),
            'device_type' => $deviceType,
            'created_at' => now(),
        ]);

        // 10. Audit Logging
        AuditService::log(
            action: AuditAction::ATTRIBUTION_CLICK_RECORDED,
            userId: null,
            entityType: 'AttributionClick',
            entityId: $click->id,
            payloadDiff: [
                'click_id' => $click->id,
                'campaign_id' => $campaign->id,
                'campaign_application_id' => $application->id,
                'tracking_code' => $application->tracking_code,
                'visitor_id' => $visitorId,
                'device_type' => $deviceType,
            ]
        );

        $cookieDays = $campaign->cookie_duration_days ?: 30;

        return [
            'success' => true,
            'click' => $click,
            'campaign' => $campaign,
            'application' => $application,
            'destination_url' => $destinationUrl,
            'visitor_id' => $visitorId,
            'cookie_days' => $cookieDays,
        ];
    }

    /**
     * Resolve last-touch attribution within campaign cookie window
     */
    public function resolveAttribution(
        Campaign $campaign,
        ?string $visitorId = null,
        ?string $trackingCode = null
    ): ?AttributionClick {
        $cookieDays = $campaign->cookie_duration_days ?: 30;
        $windowCutoff = now()->subDays($cookieDays);

        // 1. Direct tracking code match (e.g. promo code explicitly provided at checkout)
        if (!empty($trackingCode)) {
            $normalizedCode = Str::upper(trim($trackingCode));

            $click = AttributionClick::where('campaign_id', $campaign->id)
                ->whereRaw('UPPER(tracking_code) = ?', [$normalizedCode])
                ->whereHas('campaignApplication', function ($q) {
                    $q->where('status', CampaignApplicationStatus::APPROVED);
                })
                ->where('created_at', '>=', $windowCutoff)
                ->latest('created_at')
                ->first();

            if ($click) {
                return $click;
            }

            // If no click was recorded for that code yet, check if it's an approved application directly
            $application = CampaignApplication::where('campaign_id', $campaign->id)
                ->whereRaw('UPPER(tracking_code) = ?', [$normalizedCode])
                ->where('status', CampaignApplicationStatus::APPROVED)
                ->first();

            if ($application) {
                // Find latest click for this application within window
                return AttributionClick::where('campaign_application_id', $application->id)
                    ->where('created_at', '>=', $windowCutoff)
                    ->latest('created_at')
                    ->first();
            }
        }

        // 2. Visitor ID match (last-touch cookie within attribution window)
        if (!empty($visitorId)) {
            $click = AttributionClick::where('campaign_id', $campaign->id)
                ->where('visitor_id', $visitorId)
                ->whereHas('campaignApplication', function ($q) {
                    $q->where('status', CampaignApplicationStatus::APPROVED);
                })
                ->where('created_at', '>=', $windowCutoff)
                ->latest('created_at')
                ->first();

            if ($click) {
                return $click;
            }
        }

        return null;
    }

    /**
     * Calculate commission based on campaign or terms version compensation model with exact MoneyMath arithmetic
     */
    public function calculateCommission(Campaign|\App\Models\CampaignTermsVersion $terms, float|string $orderValue): float
    {
        $orderValueStr = MoneyMath::format((string) $orderValue);
        $rateOrAmount = MoneyMath::format((string) $terms->commission_value);
        $type = $terms->commission_type instanceof CampaignCommissionType
            ? $terms->commission_type
            : CampaignCommissionType::from((string) $terms->commission_type);

        $calculated = match ($type) {
            CampaignCommissionType::PERCENTAGE_CPO => MoneyMath::format(
                MoneyMath::div(
                    MoneyMath::mul($orderValueStr, $rateOrAmount, 4),
                    '100',
                    4
                ),
                2
            ),
            CampaignCommissionType::FIXED_PER_ORDER,
            CampaignCommissionType::CPA_ACTION,
            CampaignCommissionType::UGC_FLAT_FEE,
            CampaignCommissionType::HYBRID => MoneyMath::format($rateOrAmount, 2),
        };

        return (float) $calculated;
    }

    /**
     * Record and attribute an incoming conversion event (Idempotent & Deduplicated)
     */
    public function recordConversion(array $data): Conversion
    {
        // 1. Resolve Campaign
        $campaign = $data['campaign'] ?? null;
        if (!$campaign && !empty($data['campaign_id'])) {
            $campaign = Campaign::findOrFail($data['campaign_id']);
        }

        if (!$campaign instanceof Campaign) {
            throw new \InvalidArgumentException('Campaign model or valid campaign_id is required for conversion recording.');
        }

        $externalOrderId = (string) $data['external_order_id'];

        // 2. Idempotency Check: Return existing conversion if external order already processed for this campaign
        $existing = Conversion::where('campaign_id', $campaign->id)
            ->where('external_order_id', $externalOrderId)
            ->first();

        if ($existing) {
            return $existing;
        }

        // 3. Resolve Attribution (Click & Application)
        $visitorId = $data['visitor_id'] ?? null;
        $trackingCode = $data['tracking_code'] ?? null;

        $click = $this->resolveAttribution($campaign, $visitorId, $trackingCode);

        $campaignApplicationId = null;
        $attributionClickId = null;
        $commissionValue = 0.00;
        $termsVersion = null;
        $resolvedApplication = null;

        if ($click) {
            $campaignApplicationId = $click->campaign_application_id;
            $attributionClickId = $click->id;
            $resolvedApplication = $click->campaignApplication;
            $termsVersion = $click->termsVersion ?: ($click->campaignApplication?->termsVersion ?: $campaign->currentTermsVersion);
            $commissionValue = $this->calculateCommission($termsVersion ?: $campaign, (string) $data['order_value']);
        } elseif (!empty($trackingCode)) {
            // Direct promo code attribution even if click was not tracked
            $normalizedCode = Str::upper(trim($trackingCode));
            $application = CampaignApplication::where('campaign_id', $campaign->id)
                ->whereRaw('UPPER(tracking_code) = ?', [$normalizedCode])
                ->where('status', CampaignApplicationStatus::APPROVED)
                ->first();

            if ($application) {
                $campaignApplicationId = $application->id;
                $resolvedApplication = $application;
                $termsVersion = $application->termsVersion ?: $campaign->currentTermsVersion;
                $commissionValue = $this->calculateCommission($termsVersion ?: $campaign, (string) $data['order_value']);
            }
        }

        if (!$termsVersion) {
            $termsVersion = $campaign->currentTermsVersion;
        }

        $currency = $data['currency'] ?? ($campaign->currencies[0] ?? 'SAR');
        $occurredAt = !empty($data['occurred_at']) ? \Carbon\Carbon::parse($data['occurred_at']) : now();

        // 4. Construct Immutable Terms Snapshot
        $commissionTypeStr = $termsVersion
            ? ($termsVersion->commission_type instanceof CampaignCommissionType ? $termsVersion->commission_type->value : (string) $termsVersion->commission_type)
            : ($campaign->commission_type instanceof CampaignCommissionType ? $campaign->commission_type->value : (string) $campaign->commission_type);

        $termsSnapshot = [
            'terms_version_id' => $termsVersion?->id,
            'version_number' => $termsVersion?->version_number ?? 1,
            'commission_type' => $commissionTypeStr,
            'commission_value' => (string) ($termsVersion?->commission_value ?? $campaign->commission_value),
            'currency' => $currency,
            'cookie_duration_days' => (int) ($termsVersion?->cookie_duration_days ?? $campaign->cookie_duration_days),
            'order_value' => (string) $data['order_value'],
            'calculated_commission' => (string) (float) $commissionValue,
            'calculated_at' => now()->toIso8601String(),
        ];

        // 5. Automatically Lock Campaign Terms
        $campaign->lockTerms('تم تثبيت الشروط التعاقدية للحملة تلقائياً لتسجيل تحويل مبيعات.');

        // 6. Phase 9: Evaluate Fraud Detection Rules
        $fraudEval = $this->fraudService->evaluate(
            campaign: $campaign,
            click: $click,
            application: $resolvedApplication,
            orderValue: (string) $data['order_value'],
            visitorId: $visitorId ?: ($click?->visitor_id),
            occurredAt: $occurredAt
        );

        // 7. Create Conversion Entity with race condition protection
        try {
            $conversion = Conversion::create([
                'campaign_id' => $campaign->id,
                'campaign_application_id' => $campaignApplicationId,
                'attribution_click_id' => $attributionClickId,
                'terms_version_id' => $termsVersion?->id,
                'visitor_id' => $visitorId ?: ($click?->visitor_id),
                'external_order_id' => $externalOrderId,
                'conversion_type' => $data['conversion_type'] ?? 'ORDER',
                'status' => ConversionStatus::PENDING,
                'order_value' => MoneyMath::format((string) $data['order_value'], 2),
                'currency' => $currency,
                'commission_value' => MoneyMath::format((string) $commissionValue, 2),
                'occurred_at' => $occurredAt,
                'metadata' => $data['metadata'] ?? [],
                'commission_terms_snapshot' => $termsSnapshot,
                'fraud_risk_score' => $fraudEval['risk_score'],
                'is_suspicious' => $fraudEval['is_suspicious'],
                'fraud_signals' => $fraudEval['signals'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Concurrent race condition: external order was just inserted by another thread
            $existing = Conversion::where('campaign_id', $campaign->id)
                ->where('external_order_id', $externalOrderId)
                ->first();

            if ($existing) {
                return $existing;
            }

            throw $e;
        }

        // 8. Audit Trail
        AuditService::log(
            action: AuditAction::CONVERSION_RECEIVED,
            userId: null,
            entityType: 'Conversion',
            entityId: $conversion->id,
            payloadDiff: [
                'conversion_id' => $conversion->id,
                'campaign_id' => $campaign->id,
                'external_order_id' => $conversion->external_order_id,
                'order_value' => $conversion->order_value,
                'currency' => $conversion->currency,
                'fraud_risk_score' => $conversion->fraud_risk_score,
                'is_suspicious' => $conversion->is_suspicious,
            ]
        );

        if ($conversion->is_suspicious) {
            AuditService::log(
                action: AuditAction::CONVERSION_FLAGGED_SUSPICIOUS,
                userId: null,
                entityType: 'Conversion',
                entityId: $conversion->id,
                payloadDiff: [
                    'conversion_id' => $conversion->id,
                    'risk_score' => $conversion->fraud_risk_score,
                    'signals_count' => count($conversion->fraud_signals ?? []),
                    'signals' => $conversion->fraud_signals,
                ],
                status: 'SUSPICIOUS',
                reason: 'تم رصد إشارات اشتباه احتيال في التحويل وإدراجه للمراجعة والتدقيق الإداري.'
            );
        }

        if ($campaignApplicationId) {
            AuditService::log(
                action: AuditAction::CONVERSION_ATTRIBUTED,
                userId: null,
                entityType: 'Conversion',
                entityId: $conversion->id,
                payloadDiff: [
                    'conversion_id' => $conversion->id,
                    'campaign_id' => $campaign->id,
                    'campaign_application_id' => $campaignApplicationId,
                    'attribution_click_id' => $attributionClickId,
                    'commission_value' => $commissionValue,
                ]
            );

            AuditService::log(
                action: AuditAction::COMMISSION_CALCULATED,
                userId: null,
                entityType: 'Conversion',
                entityId: $conversion->id,
                payloadDiff: [
                    'conversion_id' => $conversion->id,
                    'commission_type' => $campaign->commission_type->value,
                    'commission_value' => $commissionValue,
                ]
            );
        }

        return $conversion;
    }

    /**
     * Privacy-friendly device detection
     */
    protected function detectDeviceType(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'desktop';
        }

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet') || (str_contains($ua, 'android') && !str_contains($ua, 'mobile'))) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
