<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCampaignController extends Controller
{
    /**
     * Display a listing of all platform campaigns for moderation.
     */
    public function index(Request $request): View
    {
        $query = Campaign::with(['brandProfile.user'])
            ->withCount(['applications', 'approvedApplications'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('brandProfile', function ($bq) use ($search) {
                      $bq->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $campaigns = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Campaign::count(),
            'active' => Campaign::where('status', CampaignStatus::ACTIVE)->count(),
            'paused' => Campaign::where('status', CampaignStatus::PAUSED)->count(),
            'total_applications' => \App\Models\CampaignApplication::count(),
            'premium' => Campaign::where('is_premium', true)->count(),
        ];

        $subscriptionPlans = \App\Models\SubscriptionPlan::where('status', \App\Enums\SubscriptionPlanStatus::ACTIVE)
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('admin.campaigns.index', compact('campaigns', 'stats', 'subscriptionPlans'));
    }

    /**
     * Moderation override to toggle status.
     */
    public function toggleStatus(Request $request, Campaign $campaign): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', CampaignStatus::values())],
        ]);

        $newStatus = CampaignStatus::from($request->input('status'));
        $oldStatus = $campaign->status;
        $campaign->update(['status' => $newStatus]);

        AuditService::log(
            AuditAction::CAMPAIGN_STATUS_CHANGED,
            $request->user()->id,
            'Campaign',
            $campaign->id,
            ['moderator' => 'super_admin', 'old' => $oldStatus->value, 'new' => $newStatus->value]
        );

        return back()->with('status', "تم تحديث حالة الحملة بواسطة الإدارة إلى: {$newStatus->label()}");
    }

    /**
     * Update campaign premium status and allowed plan entitlements.
     */
    public function updatePremium(Request $request, Campaign $campaign): RedirectResponse
    {
        // Authorization: only Super Admin or users with campaigns.manage_premium permission
        if (!$request->user()->isSuperAdmin() && !$request->user()->hasPermission('campaigns.manage_premium')) {
            abort(403, 'غير مصرح لك بإدارة إعدادات الحملات المميزة.');
        }

        $validated = $request->validate([
            'is_premium' => ['required', 'boolean'],
            'allowed_plan_ids' => ['nullable', 'array'],
            'allowed_plan_ids.*' => ['uuid', 'exists:subscription_plans,id'],
            'premium_requirements' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldPremium = $campaign->is_premium;
        $campaign->update([
            'is_premium' => (bool) $validated['is_premium'],
            'allowed_plan_ids' => !empty($validated['allowed_plan_ids']) ? array_values($validated['allowed_plan_ids']) : null,
            'premium_requirements' => $validated['premium_requirements'] ?? null,
        ]);

        AuditService::log(
            AuditAction::CAMPAIGN_PREMIUM_STATUS_UPDATED,
            $request->user()->id,
            'Campaign',
            $campaign->id,
            [
                'old_is_premium' => $oldPremium,
                'new_is_premium' => (bool) $validated['is_premium'],
                'allowed_plan_ids' => $campaign->allowed_plan_ids,
            ],
            status: 'SUCCESS',
            reason: "تحديث إعدادات تمييز الحملة [{$campaign->title}] بواسطة الإدارة"
        );

        $msg = $validated['is_premium']
            ? 'تم تمييز الحملة كحملة حصرية تتطلب اشتراكاً نشطاً.'
            : 'تم تعيين الحملة كحملة عادية متاحة لجميع المسوقين.';

        return back()->with('status', $msg);
    }
}
