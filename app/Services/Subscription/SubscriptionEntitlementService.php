<?php

namespace App\Services\Subscription;

use App\Enums\CampaignApplicationStatus;
use App\Enums\Currency;
use App\Enums\SubscriptionPlanStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\MarketerProfile;
use App\Models\MarketerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Collection;

class SubscriptionEntitlementService
{
    /**
     * Check if a marketer is entitled and eligible to apply for a campaign.
     *
     * @return array{
     *     allowed: bool,
     *     code?: string,
     *     reason?: string,
     *     subscription?: ?MarketerSubscription
     * }
     */
    public function canApply(User|MarketerProfile $marketer, Campaign $campaign): array
    {
        $profile = $marketer instanceof MarketerProfile ? $marketer : $marketer->marketerProfile;
        $user = $marketer instanceof User ? $marketer : $marketer->user;

        if (!$profile || !$user) {
            return [
                'allowed' => false,
                'code' => 'missing_profile',
                'reason' => 'الملف التعريفي للمسوق غير مكتمل.',
            ];
        }

        // If campaign is not premium, standard eligibility applies
        if (!$campaign->isPremium()) {
            return [
                'allowed' => true,
                'code' => 'standard_campaign',
                'subscription' => null,
            ];
        }

        // Campaign is premium: retrieve active subscription
        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            // Check if user has an expired or pending subscription for informative messaging
            $lastSub = MarketerSubscription::where('user_id', $user->id)->latest('created_at')->first();
            if ($lastSub && $lastSub->isExpired()) {
                return [
                    'allowed' => false,
                    'code' => 'subscription_expired',
                    'reason' => 'انتهت صلاحية باقة اشتراكك السابقة في ' . ($lastSub->ends_at?->format('Y-m-d') ?? '') . '. يُرجى تجديد الاشتراك للانضمام للحملات المميزة.',
                    'subscription' => $lastSub,
                ];
            }

            if ($lastSub && $lastSub->status === SubscriptionStatus::SUSPENDED) {
                return [
                    'allowed' => false,
                    'code' => 'subscription_suspended',
                    'reason' => 'تم تعليق باقة اشتراكك إدارياً: ' . ($lastSub->suspension_reason ?: 'يُرجى التواصل مع الدعم الفني.'),
                    'subscription' => $lastSub,
                ];
            }

            return [
                'allowed' => false,
                'code' => 'no_active_subscription',
                'reason' => 'هذه الحملة مميزة وحصرية وتتطلب اشتراكاً نشطاً في إحدى باقات المسوقين المعتمدة.',
                'subscription' => null,
            ];
        }

        // Check if subscription plan entitles the marketer to premium campaigns
        if (!$subscription->allowsPremiumCampaigns()) {
            return [
                'allowed' => false,
                'code' => 'plan_disallows_premium',
                'reason' => 'باقتك الحالية (' . ($subscription->plan_snapshot['name_ar'] ?? $subscription->plan?->name_ar) . ') لا تتضمن ميزة المشاركة في الحملات المميزة. يُرجى ترقية باقتك.',
                'subscription' => $subscription,
            ];
        }

        // Check if campaign restricts to specific plan IDs
        if (!$campaign->allowsPlan($subscription->subscription_plan_id)) {
            $requiredPlans = SubscriptionPlan::whereIn('id', (array) $campaign->allowed_plan_ids)->pluck('name_ar')->implode(' أو ');
            return [
                'allowed' => false,
                'code' => 'plan_tier_not_permitted',
                'reason' => "هذه الحملة الحصرية مخصصة فقط لمشتركي باقة: [{$requiredPlans}]. باقتك الحالية غير مؤهلة.",
                'subscription' => $subscription,
            ];
        }

        // Check monthly quota limit for premium campaigns if specified
        $maxPremium = $subscription->entitlement('max_premium_campaigns_per_month');
        if ($maxPremium !== null && $maxPremium > 0) {
            $sinceDate = $subscription->starts_at ? max($subscription->starts_at, now()->subDays(30)) : now()->subDays(30);

            $usedCount = CampaignApplication::where('marketer_profile_id', $profile->id)
                ->where('created_at', '>=', $sinceDate)
                ->whereHas('campaign', function ($q) {
                    $q->where('is_premium', true);
                })
                ->count();

            if ($usedCount >= $maxPremium) {
                return [
                    'allowed' => false,
                    'code' => 'monthly_limit_reached',
                    'reason' => "لقد استنفدت الحد الأقصى للتقديم على الحملات المميزة ({$usedCount}/{$maxPremium}) في دورتك الحالية. يمكنك التقديم مجدداً بعد تجديد الباقة.",
                    'subscription' => $subscription,
                ];
            }
        }

        // Check active applications limit if specified
        $maxActive = $subscription->entitlement('max_active_applications');
        if ($maxActive !== null && $maxActive > 0) {
            $activeAppsCount = CampaignApplication::where('marketer_profile_id', $profile->id)
                ->whereIn('status', [CampaignApplicationStatus::PENDING, CampaignApplicationStatus::APPROVED])
                ->count();

            if ($activeAppsCount >= $maxActive) {
                return [
                    'allowed' => false,
                    'code' => 'max_active_applications_reached',
                    'reason' => "لقد بلغت الحد الأقصى من المشاركات النشطة ({$activeAppsCount}/{$maxActive}) المسموح بها ضمن باقتك.",
                    'subscription' => $subscription,
                ];
            }
        }

        return [
            'allowed' => true,
            'code' => 'eligible',
            'subscription' => $subscription,
        ];
    }

    /**
     * Get the active subscription for a marketer.
     */
    public function getActiveSubscription(User|MarketerProfile $marketer): ?MarketerSubscription
    {
        $userId = $marketer instanceof User ? $marketer->id : $marketer->user_id;

        /** @var ?MarketerSubscription $sub */
        $sub = MarketerSubscription::where('user_id', $userId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at')
            ->first();

        return $sub;
    }

    /**
     * List all public active subscription plans with pricing in the requested currency.
     *
     * @return Collection<int, SubscriptionPlan>
     */
    public function getAvailablePlans(Currency|string $currency): Collection
    {
        $currCode = $currency instanceof Currency ? $currency->value : strtoupper(trim($currency));

        return SubscriptionPlan::where('status', SubscriptionPlanStatus::ACTIVE)
            ->with(['prices' => function ($q) use ($currCode) {
                $q->where('currency', $currCode)->where('is_active', true);
            }])
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
