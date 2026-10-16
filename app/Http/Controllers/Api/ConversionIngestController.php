<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Services\AttributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConversionIngestController extends Controller
{
    public function __construct(
        protected AttributionService $attributionService
    ) {}

    /**
     * Ingest and record an authenticated and signed conversion event.
     */
    public function ingest(Request $request): JsonResponse
    {
        // 1. Strict Input Validation (Strictly positive order value, max 2 decimals, safe strings)
        $validated = $request->validate([
            'campaign_id' => ['required', 'uuid', 'exists:campaigns,id'],
            'external_order_id' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9_\-\.\#]+$/'],
            'order_value' => ['required', 'numeric', 'gt:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'currency' => ['nullable', 'string', 'max:10'],
            'visitor_id' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'tracking_code' => ['nullable', 'string', 'max:64'],
            'conversion_type' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
        ], [
            'order_value.gt' => 'يجب أن تكون قيمة الطلب رقماً موجباً أكبر من الصفر.',
            'order_value.regex' => 'صيغة قيمة الطلب غير صالحة، الحد الأقصى خانتان عشريتان فقط.',
            'external_order_id.regex' => 'رقم الطلب يحتوي على رموز غير مسموح بها.',
        ]);

        $campaign = Campaign::findOrFail($validated['campaign_id']);

        // 2. Campaign Ownership Verification (Brand A cannot ingest for Brand B)
        /** @var BrandProfile|null $brandProfile */
        $brandProfile = $request->attributes->get('brandProfile');
        if ($brandProfile && $campaign->brand_profile_id !== $brandProfile->id) {
            return response()->json([
                'success' => false,
                'error' => 'unauthorized_campaign',
                'message' => 'غير مصرح لك بتسجيل تحويلات لحملة تتبع علامة تجارية أخرى.',
            ], 403);
        }

        // 3. Campaign Status Verification (Only ACTIVE campaigns can ingest conversions)
        if ($campaign->status !== CampaignStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'error' => 'campaign_not_active',
                'message' => 'الحملة التسويقية غير نشطة حالياً لتسجيل التحويلات.',
            ], 422);
        }

        // 4. Currency Compatibility Verification (No silent FX or currency spoofing)
        $allowedCurrencies = array_map('strtoupper', $campaign->currencies ?? ['SAR']);
        $submittedCurrency = !empty($validated['currency']) ? strtoupper(trim($validated['currency'])) : $allowedCurrencies[0];

        if (!in_array($submittedCurrency, $allowedCurrencies, true)) {
            return response()->json([
                'success' => false,
                'error' => 'unsupported_currency',
                'message' => "العملة المحددة ({$submittedCurrency}) غير مقبولة في هذه الحملة. العملات المقبولة: " . implode(', ', $allowedCurrencies),
            ], 422);
        }

        // 5. Tracking Code Security Verification (Cannot use another campaign's code or unapproved marketer)
        $trackingCode = null;
        if (!empty($validated['tracking_code'])) {
            $normalizedCode = Str::upper(trim($validated['tracking_code']));

            $application = CampaignApplication::where('campaign_id', $campaign->id)
                ->whereRaw('UPPER(tracking_code) = ?', [$normalizedCode])
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'error' => 'invalid_tracking_code',
                    'message' => 'رمز التتبع غير مرتبط بهذه الحملة التسويقية أو غير موجود.',
                ], 422);
            }

            if ($application->status !== CampaignApplicationStatus::APPROVED) {
                return response()->json([
                    'success' => false,
                    'error' => 'unapproved_marketer',
                    'message' => 'طلب المسوق صاحب رمز التتبع غير معتمد أو تم إيقافه.',
                ], 422);
            }

            $trackingCode = $application->tracking_code;
        }

        // 6. Visitor Identifier (Privacy-conscious first-party token fallback)
        $visitorId = $validated['visitor_id'] ?? $request->cookie(AttributionService::VISITOR_COOKIE);

        // 7. Server-authoritative Attribution & Commission Calculation
        // Notice: client-provided commission values, statuses, or marketer_ids are completely ignored
        $conversion = $this->attributionService->recordConversion([
            'campaign' => $campaign,
            'campaign_id' => $campaign->id,
            'external_order_id' => $validated['external_order_id'],
            'order_value' => (float) $validated['order_value'],
            'currency' => $submittedCurrency,
            'visitor_id' => $visitorId,
            'tracking_code' => $trackingCode,
            'conversion_type' => $validated['conversion_type'] ?? 'ORDER',
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام وتوثيق حدث التحويل بنجاح ومعالجة الإسناد الآلي.',
            'conversion' => [
                'id' => $conversion->id,
                'external_order_id' => $conversion->external_order_id,
                'order_value' => (float) $conversion->order_value,
                'currency' => $conversion->currency,
                'commission_value' => (float) $conversion->commission_value,
                'status' => $conversion->status->value,
                'is_attributed' => !is_null($conversion->campaign_application_id),
                'campaign_application_id' => $conversion->campaign_application_id,
                'attribution_click_id' => $conversion->attribution_click_id,
                'occurred_at' => $conversion->occurred_at?->toIso8601String(),
            ],
        ], 200);
    }
}
