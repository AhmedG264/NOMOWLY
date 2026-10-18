<?php

namespace App\Policies;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * Determine whether the user can view any campaigns.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the campaign.
     */
    public function view(?User $user, Campaign $campaign): bool
    {
        // DRAFT and ARCHIVED campaigns are strictly private to the owning brand and Super Admin
        if (in_array($campaign->status, [CampaignStatus::DRAFT, CampaignStatus::ARCHIVED], true)) {
            if (!$user) {
                return false;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            return $user->isBrand() && $campaign->brand_profile_id === $user->brandProfile?->id;
        }

        // ACTIVE, PAUSED, and COMPLETED campaigns are viewable in read-only mode
        return true;
    }

    /**
     * Determine whether the user can create campaigns.
     */
    public function create(User $user): bool
    {
        return $user->isBrand() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isBrand() && $campaign->brand_profile_id === $user->brandProfile?->id;
    }

    /**
     * Determine whether the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isBrand() && $campaign->brand_profile_id === $user->brandProfile?->id;
    }

    /**
     * Determine whether the user can manage applicants.
     */
    public function manageApplications(User $user, Campaign $campaign): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isBrand() && $campaign->brand_profile_id === $user->brandProfile?->id;
    }

    /**
     * Determine whether the user can apply to the campaign.
     */
    public function apply(User $user, Campaign $campaign): bool
    {
        if (!$user->isMarketer() || !$user->marketerProfile) {
            return false;
        }

        if ($campaign->status !== CampaignStatus::ACTIVE) {
            return false;
        }

        if ($campaign->hasAppliedBy($user->marketerProfile)) {
            return false;
        }

        // If campaign is premium, check subscription entitlements
        if ($campaign->isPremium()) {
            $entitlementService = app(\App\Services\Subscription\SubscriptionEntitlementService::class);
            $check = $entitlementService->canApply($user, $campaign);
            if (!$check['allowed']) {
                return false;
            }
        }

        return true;
    }
}
