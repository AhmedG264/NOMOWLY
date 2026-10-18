<?php

namespace App\Http\Controllers\Brand;

use App\Enums\AuditAction;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns owned by the current brand.
     */
    public function index(Request $request): View
    {
        $brandProfile = $request->user()->brandProfile;

        $query = Campaign::where('brand_profile_id', $brandProfile->id)
            ->withCount(['applications', 'approvedApplications'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $campaigns = $query->paginate(10)->withQueryString();

        $campaignIds = Campaign::where('brand_profile_id', $brandProfile->id)->pluck('id');
        $clicksCount = \App\Models\AttributionClick::whereIn('campaign_id', $campaignIds)->count();
        $conversionsCount = \App\Models\Conversion::whereIn('campaign_id', $campaignIds)->count();
        $approvedConversionsCount = \App\Models\Conversion::whereIn('campaign_id', $campaignIds)
            ->where('status', \App\Enums\ConversionStatus::APPROVED)
            ->count();
        $realConversionRate = $clicksCount > 0 ? round(($conversionsCount / $clicksCount) * 100, 2) : 0.00;
        $totalCommissionLiability = (float) \App\Models\Conversion::whereIn('campaign_id', $campaignIds)
            ->where('status', \App\Enums\ConversionStatus::APPROVED)
            ->sum('commission_value');
        $pendingCommissionLiability = (float) \App\Models\Conversion::whereIn('campaign_id', $campaignIds)
            ->where('status', \App\Enums\ConversionStatus::PENDING)
            ->sum('commission_value');

        $stats = [
            'total' => $campaignIds->count(),
            'active' => Campaign::where('brand_profile_id', $brandProfile->id)->where('status', CampaignStatus::ACTIVE)->count(),
            'total_applicants' => CampaignApplication::whereIn('campaign_id', $campaignIds)->count(),
            'approved_creators' => CampaignApplication::whereIn('campaign_id', $campaignIds)->where('status', CampaignApplicationStatus::APPROVED)->count(),
            'total_clicks' => $clicksCount,
            'total_conversions' => $conversionsCount,
            'approved_conversions' => $approvedConversionsCount,
            'conversion_rate' => $realConversionRate,
            'approved_commission' => $totalCommissionLiability,
            'pending_commission' => $pendingCommissionLiability,
        ];

        return view('brand.campaigns.index', compact('campaigns', 'stats'));
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(): View
    {
        return view('brand.campaigns.create');
    }

    /**
     * Store a newly created campaign in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'brief' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'target_markets' => ['required', 'array', 'min:1'],
            'target_markets.*' => ['string'],
            'currencies' => ['required', 'array', 'min:1'],
            'currencies.*' => ['string'],
            'commission_type' => ['required', 'string', 'in:' . implode(',', CampaignCommissionType::values())],
            'commission_value' => ['required', 'string', 'max:100'],
            'avg_basket' => ['nullable', 'string', 'max:100'],
            'conversion_rate' => ['nullable', 'string', 'max:50'],
            'cookie_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'tracking_methods' => ['required', 'array', 'min:1'],
            'tracking_methods.*' => ['string'],
            'brand_guidelines' => ['nullable', 'string'],
            'badge' => ['nullable', 'string', 'max:50'],
            'destination_url' => ['nullable', 'url', 'max:500'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $brandProfile = $request->user()->brandProfile;

        $campaign = Campaign::create([
            'brand_profile_id' => $brandProfile->id,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::lower(Str::random(5)),
            'category' => $validated['category'],
            'category_slug' => Str::slug($validated['category']),
            'description' => $validated['description'],
            'brief' => $validated['brief'],
            'requirements' => $validated['requirements'] ?? null,
            'target_markets' => $validated['target_markets'],
            'currencies' => $validated['currencies'],
            'commission_type' => $validated['commission_type'],
            'commission_value' => $validated['commission_value'],
            'avg_basket' => $validated['avg_basket'] ?? null,
            'conversion_rate' => $validated['conversion_rate'] ?? null,
            'cookie_duration_days' => $validated['cookie_duration_days'],
            'tracking_methods' => $validated['tracking_methods'],
            'brand_guidelines' => $validated['brand_guidelines'] ?? null,
            'badge' => $validated['badge'] ?? null,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'status' => CampaignStatus::ACTIVE,
            'destination_url' => $validated['destination_url'] ?? null,
            'starts_at' => now(),
        ]);

        // Phase 7: Create initial terms version v1
        app(\App\Services\Campaign\CampaignTermsService::class)->createInitialVersion($campaign, $request->user());

        AuditService::log(
            AuditAction::CAMPAIGN_CREATED,
            $request->user()->id,
            'Campaign',
            $campaign->id,
            ['title' => $campaign->title, 'status' => $campaign->status->value]
        );

        return redirect()->route('brand.campaigns.index')
            ->with('status', 'تم إنشاء الحملة التسويقية ونشرها بنجاح في سوق الحملات.');
    }

    /**
     * Show the form for editing an existing campaign.
     */
    public function edit(Campaign $campaign): View
    {
        Gate::authorize('update', $campaign);

        return view('brand.campaigns.edit', compact('campaign'));
    }

    /**
     * Update the specified campaign in storage.
     */
    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'brief' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'target_markets' => ['required', 'array', 'min:1'],
            'target_markets.*' => ['string'],
            'currencies' => ['required', 'array', 'min:1'],
            'currencies.*' => ['string'],
            'commission_type' => ['required', 'string', 'in:' . implode(',', CampaignCommissionType::values())],
            'commission_value' => ['required', 'string', 'max:100'],
            'avg_basket' => ['nullable', 'string', 'max:100'],
            'conversion_rate' => ['nullable', 'string', 'max:50'],
            'cookie_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'tracking_methods' => ['required', 'array', 'min:1'],
            'tracking_methods.*' => ['string'],
            'brand_guidelines' => ['nullable', 'string'],
            'badge' => ['nullable', 'string', 'max:50'],
            'destination_url' => ['nullable', 'url', 'max:500'],
            'destination_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'string', 'in:' . implode(',', CampaignStatus::values())],
        ]);

        $oldData = $campaign->only(['title', 'status', 'commission_value']);

        // Phase 7: Delegate update to CampaignTermsService to strictly enforce terms locking
        app(\App\Services\Campaign\CampaignTermsService::class)->updateUnlockedTerms($campaign, [
            'title' => $validated['title'],
            'category' => $validated['category'],
            'category_slug' => Str::slug($validated['category']),
            'description' => $validated['description'],
            'brief' => $validated['brief'],
            'requirements' => $validated['requirements'] ?? null,
            'target_markets' => $validated['target_markets'],
            'currencies' => $validated['currencies'],
            'commission_type' => $validated['commission_type'],
            'commission_value' => $validated['commission_value'],
            'avg_basket' => $validated['avg_basket'] ?? null,
            'conversion_rate' => $validated['conversion_rate'] ?? null,
            'cookie_duration_days' => $validated['cookie_duration_days'],
            'tracking_methods' => $validated['tracking_methods'],
            'brand_guidelines' => $validated['brand_guidelines'] ?? null,
            'destination_url' => $validated['destination_url'] ?? null,
            'badge' => $validated['badge'] ?? null,
            'status' => $validated['status'],
        ], $request->user());

        return redirect()->route('brand.campaigns.index')
            ->with('status', 'تم تحديث بيانات الحملة التسويقية بنجاح.');
    }

    /**
     * Toggle status of campaign (ACTIVE / PAUSED / COMPLETED).
     */
    public function toggleStatus(Request $request, Campaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $request->validate([
            'destination_url' => ['nullable', 'url', 'max:500'],
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
            ['old_status' => $oldStatus->value, 'new_status' => $newStatus->value]
        );

        return back()->with('status', 'تم تغيير حالة الحملة إلى: ' . $newStatus->label());
    }

    /**
     * Manage creator applications for a campaign.
     */
    public function applications(Campaign $campaign): View
    {
        Gate::authorize('manageApplications', $campaign);

        $applications = $campaign->applications()
            ->with(['marketerProfile.user', 'marketerProfile.specializations'])
            ->latest('applied_at')
            ->paginate(15);

        return view('brand.campaigns.applications', compact('campaign', 'applications'));
    }

    /**
     * Review (Approve or Reject) a creator application.
     */
    public function reviewApplication(Request $request, Campaign $campaign, CampaignApplication $application): RedirectResponse
    {
        Gate::authorize('manageApplications', $campaign);

        // Ensure application belongs to this campaign
        if ($application->campaign_id !== $campaign->id) {
            abort(404);
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:approve,reject'],
            'tracking_code' => ['nullable', 'string', 'max:64'],
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['action'] === 'approve') {
            $application->status = CampaignApplicationStatus::APPROVED;
            $application->reviewed_at = now();
            $application->review_notes = $validated['review_notes'] ?? null;

            // Phase 7: Bind application to current terms version if not already bound
            if (empty($application->terms_version_id)) {
                $application->terms_version_id = $campaign->current_terms_version_id;
                $application->accepted_terms_at = now();
            }

            if (!empty($validated['tracking_code'])) {
                $application->tracking_code = Str::upper($validated['tracking_code']);
            }
            $application->generateTrackingCredentials();
            $application->save();

            // Phase 7: Automatically lock campaign terms upon approving first creator
            $campaign->lockTerms('تم تثبيت الشروط التعاقدية للحملة تلقائياً لاعتماد أول صانع محتوى / مسوق.');

            AuditService::log(
                action: AuditAction::CAMPAIGN_APPLICATION_APPROVED,
                userId: $request->user()->id,
                entityType: 'CampaignApplication',
                entityId: $application->id,
                payloadDiff: ['tracking_code' => $application->tracking_code],
                status: 'APPROVED',
                reason: $validated['review_notes'] ?? 'تم اعتماد المسوق وتوليد كود التتبع'
            );

            return back()->with('status', 'تم اعتماد صانع المحتوى بنجاح وتوليد كود التتبع: ' . $application->tracking_code);
        } else {
            $application->status = CampaignApplicationStatus::REJECTED;
            $application->reviewed_at = now();
            $application->review_notes = $validated['review_notes'] ?? 'تم رفض الطلب بناءً على متطلبات الحملة الحالية.';
            $application->save();

            AuditService::log(
                action: AuditAction::CAMPAIGN_APPLICATION_REJECTED,
                userId: $request->user()->id,
                entityType: 'CampaignApplication',
                entityId: $application->id,
                payloadDiff: ['notes' => $application->review_notes],
                status: 'REJECTED',
                reason: $application->review_notes
            );

            return back()->with('status', 'تم رفض طلب صانع المحتوى وتحديث الحالة.');
        }
    }

    /**
     * View campaign terms versions and change request history.
     */
    public function terms(Campaign $campaign): View
    {
        Gate::authorize('update', $campaign);

        $campaign->load(['brandProfile', 'currentTermsVersion']);
        $versions = $campaign->termsVersions()->latest('version_number')->get();
        $changeRequests = $campaign->changeRequests()->with(['requestedBy', 'reviewedBy'])->latest()->get();

        return view('brand.campaigns.terms', compact('campaign', 'versions', 'changeRequests'));
    }

    /**
     * Show form to create a new change request.
     */
    public function createChangeRequest(Campaign $campaign): View
    {
        Gate::authorize('update', $campaign);

        $currentVersion = $campaign->currentTermsVersion;
        if (!$currentVersion) {
            $currentVersion = app(\App\Services\Campaign\CampaignTermsService::class)->createInitialVersion($campaign);
        }

        return view('brand.campaigns.change-request', compact('campaign', 'currentVersion'));
    }

    /**
     * Store a newly submitted change request.
     */
    public function storeChangeRequest(Request $request, Campaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'scope' => ['required', 'string', 'in:' . implode(',', \App\Enums\CampaignTermsChangeScope::values())],
            'proposed_effective_from' => ['nullable', 'date'],
            'commission_type' => ['nullable', 'string', 'in:' . implode(',', CampaignCommissionType::values())],
            'commission_value' => ['nullable', 'string', 'max:100'],
            'cookie_duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'currency' => ['nullable', 'string', 'max:10'],
            'conversion_qualification_rules' => ['nullable', 'string', 'max:1000'],
            'approval_rules' => ['nullable', 'string', 'max:1000'],
            'cancellation_refund_rules' => ['nullable', 'string', 'max:1000'],
        ]);

        $proposedTerms = array_filter([
            'commission_type' => $validated['commission_type'] ?? null,
            'commission_value' => $validated['commission_value'] ?? null,
            'cookie_duration_days' => $validated['cookie_duration_days'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'conversion_qualification_rules' => $validated['conversion_qualification_rules'] ?? null,
            'approval_rules' => $validated['approval_rules'] ?? null,
            'cancellation_refund_rules' => $validated['cancellation_refund_rules'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $service = app(\App\Services\Campaign\CampaignTermsService::class);
        $service->createChangeRequest(
            $campaign,
            $proposedTerms,
            $validated['reason'],
            $validated['scope'],
            $validated['proposed_effective_from'] ?? null,
            $request->user()
        );

        return redirect()->route('brand.campaigns.terms', $campaign)
            ->with('status', 'تم رفع طلب تعديل شروط الحملة بنجاح، وسيقوم فريق الإدارة بمراجعته وتدقيقه.');
    }
}
