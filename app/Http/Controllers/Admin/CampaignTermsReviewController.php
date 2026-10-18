<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignTermsChangeRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CampaignTermsChangeRequest;
use App\Services\Campaign\CampaignTermsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignTermsReviewController extends Controller
{
    public function __construct(
        protected CampaignTermsService $termsService
    ) {}

    /**
     * Display a listing of all campaign terms change requests.
     */
    public function index(Request $request): View
    {
        $query = CampaignTermsChangeRequest::with(['campaign.brandProfile', 'requestedBy', 'reviewedBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $changeRequests = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => CampaignTermsChangeRequest::count(),
            'pending' => CampaignTermsChangeRequest::where('status', CampaignTermsChangeRequestStatus::PENDING)->count(),
            'approved' => CampaignTermsChangeRequest::where('status', CampaignTermsChangeRequestStatus::APPROVED)->count(),
            'rejected' => CampaignTermsChangeRequest::where('status', CampaignTermsChangeRequestStatus::REJECTED)->count(),
        ];

        return view('admin.campaigns.change-requests', compact('changeRequests', 'stats'));
    }

    /**
     * Display the details and diff of a change request.
     */
    public function show(CampaignTermsChangeRequest $changeRequest): View
    {
        $changeRequest->load(['campaign.brandProfile', 'requestedBy', 'reviewedBy', 'currentVersion']);

        return view('admin.campaigns.change-request-show', compact('changeRequest'));
    }

    /**
     * Approve a change request and upgrade terms version.
     */
    public function approve(Request $request, CampaignTermsChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reviewer_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $newVersion = $this->termsService->approveChangeRequest(
            $changeRequest,
            $request->user(),
            $validated['reviewer_notes'] ?? null
        );

        return redirect()->route('admin.campaigns.change-requests.show', $changeRequest)
            ->with('status', "تم اعتماد طلب التعديل بنجاح وترقية الحملة إلى الإصدار التعاقدي رقم v{$newVersion->version_number}.");
    }

    /**
     * Reject a change request with a mandatory reason.
     */
    public function reject(Request $request, CampaignTermsChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reviewer_notes' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->termsService->rejectChangeRequest(
            $changeRequest,
            $request->user(),
            $validated['reviewer_notes']
        );

        return redirect()->route('admin.campaigns.change-requests.show', $changeRequest)
            ->with('status', 'تم رفض طلب تعديل الشروط وتسجيل السبب في سجل التدقيق بنجاح.');
    }
}
