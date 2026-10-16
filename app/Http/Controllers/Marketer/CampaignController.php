<?php

namespace App\Http\Controllers\Marketer;

use App\Enums\AuditAction;
use App\Enums\CampaignApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns applied to by the marketer.
     */
    public function index(Request $request): View
    {
        $marketerProfile = $request->user()->marketerProfile;

        $query = CampaignApplication::where('marketer_profile_id', $marketerProfile->id)
            ->with(['campaign.brandProfile'])
            ->latest('applied_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $applications = $query->paginate(10)->withQueryString();

        $applicationIds = CampaignApplication::where('marketer_profile_id', $marketerProfile->id)->pluck('id');
        $clicksCount = \App\Models\AttributionClick::whereIn('campaign_application_id', $applicationIds)->count();
        $conversionsCount = \App\Models\Conversion::whereIn('campaign_application_id', $applicationIds)->count();
        $approvedCommission = (float) \App\Models\Conversion::whereIn('campaign_application_id', $applicationIds)
            ->where('status', \App\Enums\ConversionStatus::APPROVED)
            ->sum('commission_value');
        $pendingCommission = (float) \App\Models\Conversion::whereIn('campaign_application_id', $applicationIds)
            ->where('status', \App\Enums\ConversionStatus::PENDING)
            ->sum('commission_value');

        $stats = [
            'total' => $applicationIds->count(),
            'approved' => CampaignApplication::where('marketer_profile_id', $marketerProfile->id)->where('status', CampaignApplicationStatus::APPROVED)->count(),
            'pending' => CampaignApplication::where('marketer_profile_id', $marketerProfile->id)->where('status', CampaignApplicationStatus::PENDING)->count(),
            'rejected' => CampaignApplication::where('marketer_profile_id', $marketerProfile->id)->where('status', CampaignApplicationStatus::REJECTED)->count(),
            'total_clicks' => $clicksCount,
            'total_conversions' => $conversionsCount,
            'approved_earnings' => $approvedCommission,
            'pending_earnings' => $pendingCommission,
        ];

        return view('marketer.campaigns.index', compact('applications', 'stats'));
    }

    /**
     * Submit an application to join a campaign.
     */
    public function apply(Request $request, Campaign $campaign): RedirectResponse
    {
        Gate::authorize('apply', $campaign);

        $validated = $request->validate([
            'pitch_note' => ['nullable', 'string', 'max:1000'],
            'channels' => ['nullable', 'string', 'max:500'],
        ]);

        $marketerProfile = $request->user()->marketerProfile;

        // Channel urls parsing
        $channelUrls = [];
        if (!empty($validated['channels'])) {
            $rawLines = preg_split('/[\r\n,]+/', $validated['channels']);
            $channelUrls = array_values(array_filter(array_map('trim', $rawLines)));
        }

        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $marketerProfile->id,
            'status' => CampaignApplicationStatus::PENDING,
            'pitch_note' => $validated['pitch_note'] ?? null,
            'channel_urls' => $channelUrls,
            'applied_at' => now(),
        ]);

        AuditService::log(
            AuditAction::CAMPAIGN_APPLICATION_SUBMITTED,
            $request->user()->id,
            'CampaignApplication',
            $application->id,
            ['campaign_id' => $campaign->id, 'campaign_title' => $campaign->title]
        );

        return redirect()->route('marketplace.show', $campaign->slug)
            ->with('status', 'تم إرسال طلب انضمامك للحملة بنجاح، وستتم مراجعته وتفعيل كود التتبع من قبل العلامة التجارية قريباً.');
    }
}
