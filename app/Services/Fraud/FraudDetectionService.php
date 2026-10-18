<?php

namespace App\Services\Fraud;

use App\Enums\AuditAction;
use App\Enums\ConversionStatus;
use App\Models\AttributionClick;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\Conversion;
use App\Models\FraudDetectionRule;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FraudDetectionService
{
    /**
     * Minimum score required to flag conversion as suspicious
     */
    public const SUSPICIOUS_SCORE_THRESHOLD = 40;

    /**
     * Evaluate incoming conversion against active fraud detection rules
     *
     * @return array{
     *   risk_score: int,
     *   is_suspicious: bool,
     *   signals: array<int, array{
     *     rule_key: string,
     *     name_ar: string,
     *     weight: int,
     *     evidence: array<string, mixed>
     *   }>
     * }
     */
    public function evaluate(
        Campaign $campaign,
        ?AttributionClick $click,
        ?CampaignApplication $application,
        float|string $orderValue,
        ?string $visitorId = null,
        ?Carbon $occurredAt = null
    ): array {
        $occurredAt = $occurredAt ?: now();
        $rules = FraudDetectionRule::active()->get()->keyBy('rule_key');

        $signals = [];
        $totalRiskScore = 0;

        // 1. Rule: Rapid Conversion (Bot speed checkout after click)
        if ($click && isset($rules['rapid_conversion_seconds'])) {
            $rule = $rules['rapid_conversion_seconds'];
            $threshold = (int) $rule->threshold_value;

            if ($click->created_at) {
                $elapsedSeconds = abs($occurredAt->getTimestamp() - $click->created_at->getTimestamp());
                if ($elapsedSeconds < $threshold) {
                    $weight = (int) $rule->risk_weight;
                    $totalRiskScore += $weight;
                    $signals[] = [
                        'rule_key' => 'rapid_conversion_seconds',
                        'name_ar' => $rule->name_ar,
                        'weight' => $weight,
                        'evidence' => [
                            'elapsed_seconds' => $elapsedSeconds,
                            'threshold_seconds' => $threshold,
                            'click_time' => $click->created_at->toIso8601String(),
                            'conversion_time' => $occurredAt->toIso8601String(),
                        ],
                    ];
                }
            }
        }

        // 2. Rule: Click Burst Limit (Excessive click flood from same fingerprint/IP in 60s)
        if ($click && isset($rules['click_burst_limit'])) {
            $rule = $rules['click_burst_limit'];
            $threshold = (int) $rule->threshold_value;

            $recentClicksCount = AttributionClick::where('campaign_id', $campaign->id)
                ->where('created_at', '>=', now()->subSeconds(60))
                ->where(function ($q) use ($click) {
                    $q->where('ip_hash', $click->ip_hash)
                      ->orWhere('visitor_id', $click->visitor_id);
                })
                ->count();

            if ($recentClicksCount > $threshold) {
                $weight = (int) $rule->risk_weight;
                $totalRiskScore += $weight;
                $signals[] = [
                    'rule_key' => 'click_burst_limit',
                    'name_ar' => $rule->name_ar,
                    'weight' => $weight,
                    'evidence' => [
                        'burst_count' => $recentClicksCount,
                        'threshold' => $threshold,
                        'window_seconds' => 60,
                    ],
                ];
            }
        }

        // 3. Rule: Conversion Velocity Window (Multiple conversions from same visitor in 120s)
        $effectiveVisitorId = $visitorId ?: ($click?->visitor_id);
        if ($effectiveVisitorId && isset($rules['conversion_velocity_window'])) {
            $rule = $rules['conversion_velocity_window'];
            $thresholdSeconds = (int) $rule->threshold_value;

            $recentConversions = Conversion::where('campaign_id', $campaign->id)
                ->where('visitor_id', $effectiveVisitorId)
                ->where('created_at', '>=', now()->subSeconds($thresholdSeconds))
                ->count();

            if ($recentConversions >= 1) {
                $weight = (int) $rule->risk_weight;
                $totalRiskScore += $weight;
                $signals[] = [
                    'rule_key' => 'conversion_velocity_window',
                    'name_ar' => $rule->name_ar,
                    'weight' => $weight,
                    'evidence' => [
                        'prior_conversions_in_window' => $recentConversions,
                        'window_seconds' => $thresholdSeconds,
                    ],
                ];
            }
        }

        // 4. Rule: High Order Value Multiplier
        if (isset($rules['high_order_value_multiplier'])) {
            $rule = $rules['high_order_value_multiplier'];
            $multiplier = (float) $rule->threshold_value;

            $avgOrder = (float) (Conversion::where('campaign_id', $campaign->id)->avg('order_value') ?: 150);
            $numericOrderValue = (float) $orderValue;

            if ($numericOrderValue > ($avgOrder * $multiplier) && $numericOrderValue > 3000) {
                $weight = (int) $rule->risk_weight;
                $totalRiskScore += $weight;
                $signals[] = [
                    'rule_key' => 'high_order_value_multiplier',
                    'name_ar' => $rule->name_ar,
                    'weight' => $weight,
                    'evidence' => [
                        'order_value' => $numericOrderValue,
                        'average_order_value' => round($avgOrder, 2),
                        'multiplier_applied' => $multiplier,
                    ],
                ];
            }
        }

        // 5. Marketer High Cancellation Rate Anomaly
        if ($application) {
            $totalMarketerConversions = Conversion::where('campaign_application_id', $application->id)->count();
            if ($totalMarketerConversions >= 5) {
                $cancelledCount = Conversion::where('campaign_application_id', $application->id)
                    ->where('status', ConversionStatus::CANCELLED)
                    ->count();

                $cancellationRate = ($cancelledCount / $totalMarketerConversions) * 100;
                if ($cancellationRate >= 40) {
                    $weight = 25;
                    $totalRiskScore += $weight;
                    $signals[] = [
                        'rule_key' => 'marketer_high_cancellation_rate',
                        'name_ar' => 'نسبة إلغاء واسترداد شاذة لدى المسوق',
                        'weight' => $weight,
                        'evidence' => [
                            'cancellation_rate_pct' => round($cancellationRate, 1),
                            'total_conversions' => $totalMarketerConversions,
                            'cancelled_conversions' => $cancelledCount,
                        ],
                    ];
                }
            }
        }

        // Cap risk score between 0 and 100
        $riskScore = min(100, max(0, $totalRiskScore));
        $isSuspicious = $riskScore >= self::SUSPICIOUS_SCORE_THRESHOLD || count($signals) >= 2;

        return [
            'risk_score' => $riskScore,
            'is_suspicious' => $isSuspicious,
            'signals' => $signals,
        ];
    }

    /**
     * Conduct an administrative fraud investigation on a conversion
     */
    public function investigate(
        Conversion $conversion,
        User $investigator,
        string $decision,
        string $notes
    ): Conversion {
        return DB::transaction(function () use ($conversion, $investigator, $decision, $notes) {
            $locked = Conversion::where('id', $conversion->id)->lockForUpdate()->firstOrFail();

            if ($decision === 'CLEAR_SUSPICION') {
                $locked->update([
                    'is_suspicious' => false,
                    'investigated_at' => now(),
                    'investigated_by_user_id' => $investigator->id,
                    'investigation_notes' => $notes,
                ]);

                AuditService::log(
                    action: AuditAction::CONVERSION_INVESTIGATED,
                    userId: $investigator->id,
                    entityType: Conversion::class,
                    entityId: $locked->id,
                    payloadDiff: [
                        'conversion_id' => $locked->id,
                        'decision' => 'CLEAR_SUSPICION',
                        'notes' => $notes,
                        'fraud_risk_score' => $locked->fraud_risk_score,
                    ],
                    status: 'RESOLVED',
                    reason: "تم تدقيق التحويل وتبرئته من شبهة الاحتيال: {$notes}"
                );
            } elseif ($decision === 'CONFIRM_FRAUD_REJECT') {
                $locked->update([
                    'status' => ConversionStatus::REJECTED,
                    'rejection_reason' => "تم الرفض الأمني بعد التحقيق الإداري: {$notes}",
                    'investigated_at' => now(),
                    'investigated_by_user_id' => $investigator->id,
                    'investigation_notes' => $notes,
                ]);

                AuditService::log(
                    action: AuditAction::CONVERSION_REJECTED,
                    userId: $investigator->id,
                    entityType: Conversion::class,
                    entityId: $locked->id,
                    payloadDiff: [
                        'conversion_id' => $locked->id,
                        'decision' => 'CONFIRM_FRAUD_REJECT',
                        'notes' => $notes,
                        'fraud_risk_score' => $locked->fraud_risk_score,
                    ],
                    status: 'REJECTED',
                    reason: "رفض التحويل بداعي الاحتيال: {$notes}"
                );
            } else {
                throw new \InvalidArgumentException("قرار التحقيق غير صالح: [{$decision}].");
            }

            return $locked->fresh(['investigatedBy']);
        });
    }
}
