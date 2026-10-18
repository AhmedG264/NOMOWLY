<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Enums\PayoutRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CommissionSettlement;
use App\Models\PayoutRequest;
use App\Services\Ledger\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutRequestController extends Controller
{
    public function __construct(
        protected PayoutService $payoutService
    ) {}

    /**
     * List all payout requests with status and currency filters
     */
    public function index(Request $request): View
    {
        $query = PayoutRequest::with(['user.marketerProfile', 'wallet', 'reviewer', 'payer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($sq) use ($search) {
                $sq->where('request_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $payoutRequests = $query->latest('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => PayoutRequest::count(),
            'pending' => PayoutRequest::where('status', PayoutRequestStatus::REQUESTED)->count(),
            'approved' => PayoutRequest::where('status', PayoutRequestStatus::APPROVED)->count(),
            'paid' => PayoutRequest::where('status', PayoutRequestStatus::PAID)->count(),
            'rejected' => PayoutRequest::where('status', PayoutRequestStatus::REJECTED)->count(),
        ];

        return view('admin.finance.payouts.index', [
            'payoutRequests' => $payoutRequests,
            'stats' => $stats,
            'currencies' => Currency::cases(),
            'statuses' => PayoutRequestStatus::cases(),
            'filters' => $request->only(['status', 'currency', 'q']),
        ]);
    }

    /**
     * Show single payout request details
     */
    public function show(PayoutRequest $payoutRequest): View
    {
        $payoutRequest->load([
            'user.marketerProfile',
            'wallet',
            'reviewer',
            'payer',
            'payoutTransaction.entries.account',
        ]);

        return view('admin.finance.payouts.show', [
            'payoutRequest' => $payoutRequest,
        ]);
    }

    /**
     * Approve payout request
     */
    public function approve(Request $request, PayoutRequest $payoutRequest): RedirectResponse
    {
        try {
            $this->payoutService->approvePayoutRequest($payoutRequest, $request->user());
            return back()->with('status', "تم اعتماد طلب سحب الأرباح رقم [{$payoutRequest->request_number}] بنجاح، وبانتظار التنفيذ المصرفي.");
        } catch (\Throwable $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    /**
     * Reject payout request
     */
    public function reject(Request $request, PayoutRequest $payoutRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ], [
            'reason.required' => 'يجب ذكر سبب واضح ومحدد لرفض طلب السحب.',
            'reason.min' => 'سبب الرفض يجب ألا يقل عن 5 أحرف.',
        ]);

        try {
            $this->payoutService->rejectPayoutRequest($payoutRequest, $request->user(), $validated['reason']);
            return back()->with('status', "تم رفض طلب سحب الأرباح رقم [{$payoutRequest->request_number}] وإعادة المبلغ المحجوز فورياً إلى رصيد المسوق المتاح.");
        } catch (\Throwable $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    /**
     * Mark payout as paid (Confirm bank transfer and post double-entry transaction)
     */
    public function markPaid(Request $request, PayoutRequest $payoutRequest): RedirectResponse
    {
        $validated = $request->validate([
            'payment_reference' => 'required|string|max:128',
            'notes' => 'nullable|string|max:500',
        ], [
            'payment_reference.required' => 'الرقم المرجعي للتحويل البنكي إلزامي لتأكيد عملية الصرف.',
        ]);

        try {
            $this->payoutService->markAsPaid(
                $payoutRequest,
                $request->user(),
                $validated['payment_reference'],
                $validated['notes'] ?? null
            );

            return back()->with('status', "تم تأكيد صرف الأرباح للمسوق وترحيل القيود في دفتر الأستاذ بنجاح.");
        } catch (\Throwable $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    /**
     * List all commission settlements
     */
    public function settlements(Request $request): View
    {
        $query = CommissionSettlement::with([
            'conversion.campaign.brandProfile',
            'conversion.campaignApplication.marketerProfile',
            'ledgerTransaction',
            'reversalLedgerTransaction',
            'brandWallet.user',
            'marketerWallet.user',
            'settler',
        ]);

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        $settlements = $query->latest('settled_at')->paginate(25)->withQueryString();

        return view('admin.finance.settlements.index', [
            'settlements' => $settlements,
            'currencies' => Currency::cases(),
            'filters' => $request->only(['currency']),
        ]);
    }
}
