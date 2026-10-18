<?php

namespace App\Services\Campaign;

use App\Enums\AuditAction;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignTermsChangeRequestStatus;
use App\Enums\CampaignTermsChangeScope;
use App\Enums\CampaignTermsVersionStatus;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\CampaignTermsChangeRequest;
use App\Models\CampaignTermsVersion;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class CampaignTermsService
{
    /**
     * Create initial terms version (v1) for a newly created campaign.
     */
    public function createInitialVersion(Campaign $campaign, ?User $creator = null): CampaignTermsVersion
    {
        $existing = $campaign->termsVersions()->where('version_number', 1)->first();
        if ($existing) {
            if (!$campaign->current_terms_version_id) {
                $campaign->update(['current_terms_version_id' => $existing->id]);
            }
            return $existing;
        }

        $currencies = is_array($campaign->currencies) ? $campaign->currencies : (array) $campaign->currencies;
        $mainCurrency = $currencies[0] ?? 'SAR';

        $version = CampaignTermsVersion::create([
            'campaign_id' => $campaign->id,
            'version_number' => 1,
            'status' => CampaignTermsVersionStatus::ACTIVE,
            'title' => $campaign->title,
            'description' => $campaign->description,
            'brief' => $campaign->brief,
            'commission_type' => $campaign->commission_type instanceof CampaignCommissionType ? $campaign->commission_type : CampaignCommissionType::from((string) $campaign->commission_type),
            'commission_value' => (string) $campaign->commission_value,
            'currency' => $mainCurrency,
            'currencies' => $currencies,
            'cookie_duration_days' => (int) ($campaign->cookie_duration_days ?: 30),
            'conversion_qualification_rules' => 'احتساب مباشر للمبيعات المكتملة عبر الرابط التتبعي أو كود الخصم ضمن فترة الكوكيز.',
            'approval_rules' => 'مراجعة المبيعات خلال 14 يوماً من تاريخ الطلب واستبعاد الطلبات المرتجعة أو الملغاة.',
            'cancellation_refund_rules' => 'يتم عكس العمولة في حال استرجاع العميل للطلب بالكامل قبل انقضاء فترة المرتجعات.',
            'effective_from' => $campaign->starts_at ?? now(),
            'change_summary' => 'الإصدار الأولي التعاقدي للحملة التسويقية (v1)',
            'created_by_user_id' => $creator?->id ?? $campaign->brandProfile?->user_id,
        ]);

        $campaign->update([
            'current_terms_version_id' => $version->id,
        ]);

        AuditService::log(
            action: AuditAction::CAMPAIGN_TERMS_VERSION_CREATED,
            userId: $creator?->id,
            entityType: 'CampaignTermsVersion',
            entityId: $version->id,
            payloadDiff: [
                'campaign_id' => $campaign->id,
                'version_number' => 1,
                'commission_type' => $version->commission_type->value,
                'commission_value' => $version->commission_value,
                'cookie_duration_days' => $version->cookie_duration_days,
            ],
            reason: 'إنشاء الإصدار الأولي لشروط الحملة'
        );

        return $version;
    }

    /**
     * Check if terms are strictly locked from direct modification
     */
    public function isTermsLocked(Campaign $campaign): bool
    {
        return $campaign->isTermsLocked();
    }

    /**
     * Lock campaign terms explicitly
     */
    public function lockTerms(Campaign $campaign, string $reason): void
    {
        $campaign->lockTerms($reason);
    }

    /**
     * Update campaign when terms are unlocked (e.g. before any marketers joined)
     */
    public function updateUnlockedTerms(Campaign $campaign, array $data, User $user): Campaign
    {
        // 1. Check if terms are locked
        if ($this->isTermsLocked($campaign)) {
            // Check if any financial terms are being modified
            $termsFields = ['commission_type', 'commission_value', 'cookie_duration_days', 'currencies'];
            $isModifyingTerms = false;
            foreach ($termsFields as $field) {
                if (isset($data[$field])) {
                    $existingVal = $campaign->{$field};
                    if ($field === 'commission_type' && $existingVal instanceof CampaignCommissionType) {
                        $existingVal = $existingVal->value;
                    }
                    if ($field === 'currencies' && is_array($existingVal)) {
                        $existingVal = json_encode($existingVal);
                        $newVal = json_encode($data[$field]);
                        if ($existingVal !== $newVal) {
                            $isModifyingTerms = true;
                            break;
                        }
                    } elseif ((string) $existingVal !== (string) $data[$field]) {
                        $isModifyingTerms = true;
                        break;
                    }
                }
            }

            if ($isModifyingTerms) {
                throw ValidationException::withMessages([
                    'terms' => 'تم تثبيت الشروط التعاقدية والمالية لهذه الحملة نظراً لبدء نشاط المسوقين أو تسجيل إحالات. لا يمكن التعديل المباشر على نسب العمولات، أو فترة الكوكيز، أو العملات بأثر رجعي. يرجى تقديم طلب تغيير شروط (Campaign Terms Change Request).',
                ]);
            }
        }

        // 2. Perform regular update
        $oldData = $campaign->only(['title', 'status', 'commission_value', 'cookie_duration_days']);
        $campaign->update($data);

        // 3. Keep current active v1 in sync if not locked yet
        if (!$campaign->is_terms_locked && $campaign->current_terms_version_id) {
            $currentVersion = $campaign->currentTermsVersion;
            if ($currentVersion && $currentVersion->version_number === 1) {
                $currentVersion->update([
                    'title' => $campaign->title,
                    'description' => $campaign->description,
                    'brief' => $campaign->brief,
                    'commission_type' => $campaign->commission_type,
                    'commission_value' => (string) $campaign->commission_value,
                    'cookie_duration_days' => (int) $campaign->cookie_duration_days,
                    'currencies' => $campaign->currencies,
                ]);
            }
        }

        AuditService::log(
            AuditAction::CAMPAIGN_UPDATED,
            $user->id,
            'Campaign',
            $campaign->id,
            ['old' => $oldData, 'new' => $campaign->only(['title', 'status', 'commission_value', 'cookie_duration_days'])]
        );

        return $campaign;
    }

    /**
     * Submit a Campaign Terms Change Request
     */
    public function createChangeRequest(
        Campaign $campaign,
        array $proposedData,
        string $reason,
        string $scope = 'NEW_MARKETERS_ONLY',
        ?string $effectiveFrom = null,
        User $requestedBy
    ): CampaignTermsChangeRequest {
        $scopeEnum = CampaignTermsChangeScope::tryFrom($scope) ?? CampaignTermsChangeScope::NEW_MARKETERS_ONLY;

        // Ensure campaign has active initial version
        $currentVersion = $campaign->currentTermsVersion;
        if (!$currentVersion) {
            $currentVersion = $this->createInitialVersion($campaign, $requestedBy);
        }

        $previousTerms = $currentVersion->toTermsArray();

        // Calculate diff between previous terms and proposed terms
        $diff = $this->calculateDiff($previousTerms, $proposedData);

        if (empty($diff)) {
            throw ValidationException::withMessages([
                'proposed_terms' => 'لم يتم إدخال أي تعديلات حقيقية على الشروط الحالية لإنشاء طلب التغيير.',
            ]);
        }

        $effectiveDate = $effectiveFrom ? Carbon::parse($effectiveFrom) : now();

        $changeRequest = CampaignTermsChangeRequest::create([
            'campaign_id' => $campaign->id,
            'current_version_id' => $currentVersion->id,
            'status' => CampaignTermsChangeRequestStatus::PENDING,
            'scope' => $scopeEnum,
            'reason' => trim($reason),
            'proposed_effective_from' => $effectiveDate,
            'proposed_terms' => $proposedData,
            'previous_terms_snapshot' => $previousTerms,
            'diff_summary' => $diff,
            'requested_by_user_id' => $requestedBy->id,
        ]);

        AuditService::log(
            action: AuditAction::CAMPAIGN_TERMS_CHANGE_REQUESTED,
            userId: $requestedBy->id,
            entityType: 'CampaignTermsChangeRequest',
            entityId: $changeRequest->id,
            payloadDiff: [
                'campaign_id' => $campaign->id,
                'scope' => $scopeEnum->value,
                'diff' => $diff,
                'reason' => $reason,
            ],
            reason: $reason
        );

        return $changeRequest;
    }

    /**
     * Admin review: Approve a Campaign Terms Change Request and create new version
     */
    public function approveChangeRequest(
        CampaignTermsChangeRequest $request,
        User $reviewer,
        ?string $notes = null
    ): CampaignTermsVersion {
        if ($request->status !== CampaignTermsChangeRequestStatus::PENDING) {
            throw new RuntimeException("لا يمكن اعتماد طلب تغيير شروط حالته الحالية ليست معلقة: [{$request->status->value}].");
        }

        return DB::transaction(function () use ($request, $reviewer, $notes) {
            $campaign = Campaign::where('id', $request->campaign_id)->lockForUpdate()->firstOrFail();

            $nextVersionNumber = ((int) $campaign->termsVersions()->max('version_number')) + 1;
            $currentVersion = $campaign->currentTermsVersion;

            // 1. Mark previous version as SUPERSEDED
            if ($currentVersion) {
                $currentVersion->update([
                    'status' => CampaignTermsVersionStatus::SUPERSEDED,
                    'effective_until' => now(),
                ]);
            }

            $proposed = $request->proposed_terms;

            $commissionType = isset($proposed['commission_type'])
                ? (CampaignCommissionType::tryFrom($proposed['commission_type']) ?? $campaign->commission_type)
                : $campaign->commission_type;

            $commissionValue = (string) ($proposed['commission_value'] ?? $campaign->commission_value);
            $cookieDays = (int) ($proposed['cookie_duration_days'] ?? $campaign->cookie_duration_days);
            $currencies = $proposed['currencies'] ?? $campaign->currencies;
            $mainCurrency = $proposed['currency'] ?? ($currencies[0] ?? 'SAR');

            // 2. Create New Terms Version
            $newVersion = CampaignTermsVersion::create([
                'campaign_id' => $campaign->id,
                'version_number' => $nextVersionNumber,
                'status' => CampaignTermsVersionStatus::ACTIVE,
                'title' => $proposed['title'] ?? $campaign->title,
                'description' => $proposed['description'] ?? $campaign->description,
                'brief' => $proposed['brief'] ?? $campaign->brief,
                'commission_type' => $commissionType,
                'commission_value' => $commissionValue,
                'currency' => $mainCurrency,
                'currencies' => $currencies,
                'cookie_duration_days' => $cookieDays,
                'conversion_qualification_rules' => $proposed['conversion_qualification_rules'] ?? ($currentVersion?->conversion_qualification_rules),
                'approval_rules' => $proposed['approval_rules'] ?? ($currentVersion?->approval_rules),
                'cancellation_refund_rules' => $proposed['cancellation_refund_rules'] ?? ($currentVersion?->cancellation_refund_rules),
                'effective_from' => $request->proposed_effective_from ?? now(),
                'change_summary' => "ترقية الشروط للإصدار (v{$nextVersionNumber}) بناءً على طلب التعديل: " . $request->reason,
                'created_by_user_id' => $request->requested_by_user_id,
            ]);

            // 3. Update Campaign pointer and cached fields
            $campaign->update([
                'current_terms_version_id' => $newVersion->id,
                'commission_type' => $commissionType,
                'commission_value' => $commissionValue,
                'cookie_duration_days' => $cookieDays,
                'currencies' => $currencies,
                'is_terms_locked' => true,
                'terms_locked_at' => $campaign->terms_locked_at ?? now(),
                'terms_lock_reason' => $campaign->terms_lock_reason ?? 'تم تثبيت الشروط بوجود مسوقين وإصدارات معتمدة.',
            ]);

            // 4. Update Change Request
            $request->update([
                'status' => CampaignTermsChangeRequestStatus::APPROVED,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewer_notes' => $notes,
                'reviewed_at' => now(),
                'applied_terms_version_id' => $newVersion->id,
            ]);

            // 5. Handle Scope Application:
            // If scope is ALL_MARKETERS, apply to all current applications automatically
            if ($request->scope === CampaignTermsChangeScope::ALL_MARKETERS) {
                CampaignApplication::where('campaign_id', $campaign->id)
                    ->update([
                        'terms_version_id' => $newVersion->id,
                        'accepted_terms_at' => now(),
                    ]);
            }

            // 6. Audit Logging
            AuditService::log(
                action: AuditAction::CAMPAIGN_TERMS_CHANGE_APPROVED,
                userId: $reviewer->id,
                entityType: 'CampaignTermsChangeRequest',
                entityId: $request->id,
                payloadDiff: [
                    'request_id' => $request->id,
                    'campaign_id' => $campaign->id,
                    'new_version' => $nextVersionNumber,
                    'new_version_id' => $newVersion->id,
                    'reviewer_id' => $reviewer->id,
                    'notes' => $notes,
                ],
                reason: $notes ?? 'اعتماد طلب تغيير الشروط'
            );

            AuditService::log(
                action: AuditAction::CAMPAIGN_TERMS_VERSION_CREATED,
                userId: $reviewer->id,
                entityType: 'CampaignTermsVersion',
                entityId: $newVersion->id,
                payloadDiff: [
                    'campaign_id' => $campaign->id,
                    'version_number' => $nextVersionNumber,
                    'commission_type' => $newVersion->commission_type->value,
                    'commission_value' => $newVersion->commission_value,
                ],
                reason: "إنشاء الإصدار الجديد v{$nextVersionNumber}"
            );

            return $newVersion;
        });
    }

    /**
     * Admin review: Reject a Campaign Terms Change Request
     */
    public function rejectChangeRequest(
        CampaignTermsChangeRequest $request,
        User $reviewer,
        string $notes
    ): void {
        if ($request->status !== CampaignTermsChangeRequestStatus::PENDING) {
            throw new RuntimeException("لا يمكن رفض طلب تغيير شروط حالته الحالية ليست معلقة: [{$request->status->value}].");
        }

        $request->update([
            'status' => CampaignTermsChangeRequestStatus::REJECTED,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewer_notes' => trim($notes),
            'reviewed_at' => now(),
        ]);

        AuditService::log(
            action: AuditAction::CAMPAIGN_TERMS_CHANGE_REJECTED,
            userId: $reviewer->id,
            entityType: 'CampaignTermsChangeRequest',
            entityId: $request->id,
            payloadDiff: [
                'request_id' => $request->id,
                'campaign_id' => $request->campaign_id,
                'reviewer_id' => $reviewer->id,
                'notes' => $notes,
            ],
            status: 'REJECTED',
            reason: $notes
        );
    }

    /**
     * Calculate field-by-field diff between old and new terms
     */
    public function calculateDiff(array $oldTerms, array $newTerms): array
    {
        $diff = [];
        $comparableKeys = [
            'commission_type' => 'نوع العمولة',
            'commission_value' => 'قيمة العمولة',
            'cookie_duration_days' => 'مدة الكوكيز (الأيام)',
            'currency' => 'العملة الأساسية',
            'conversion_qualification_rules' => 'شروط احتساب التحويل',
            'approval_rules' => 'شروط الموافقة والاعتماد',
            'cancellation_refund_rules' => 'قواعد الإلغاء والاسترداد',
        ];

        foreach ($comparableKeys as $key => $label) {
            if (isset($newTerms[$key])) {
                $oldVal = (string) ($oldTerms[$key] ?? '');
                $newVal = (string) $newTerms[$key];

                if ($oldVal !== $newVal) {
                    $diff[$key] = [
                        'label' => $label,
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];
                }
            }
        }

        return $diff;
    }

    /**
     * Marketer explicitly accepts a new terms version
     */
    public function marketerAcceptVersion(
        CampaignApplication $application,
        CampaignTermsVersion $version,
        User $marketerUser
    ): void {
        if ($application->marketerProfile?->user_id !== $marketerUser->id) {
            throw new RuntimeException('غير مصرح لك بقبول الشروط لطلب مشاركة مسوق آخر.');
        }

        if ($application->campaign_id !== $version->campaign_id) {
            throw new RuntimeException('الإصدار لا ينتمي إلى الحملة المحددة.');
        }

        $oldVersionId = $application->terms_version_id;

        $application->update([
            'terms_version_id' => $version->id,
            'accepted_terms_at' => now(),
        ]);

        AuditService::log(
            action: AuditAction::CAMPAIGN_TERMS_ACCEPTED_BY_MARKETER,
            userId: $marketerUser->id,
            entityType: 'CampaignApplication',
            entityId: $application->id,
            payloadDiff: [
                'application_id' => $application->id,
                'old_version_id' => $oldVersionId,
                'new_version_id' => $version->id,
                'version_number' => $version->version_number,
            ],
            reason: "موافقة المسوق على إصدار الشروط رقم {$version->version_number}"
        );
    }
}
