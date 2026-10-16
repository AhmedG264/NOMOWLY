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
        ];

        return view('admin.campaigns.index', compact('campaigns', 'stats'));
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
}
