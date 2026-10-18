<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentDeposit;
use App\Models\PaymentRefund;
use App\Models\User;
use App\Services\Payment\PaymentDepositService;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentManagementController extends Controller
{
    public function __construct(
        protected PaymentDepositService $depositService,
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Display listing of payment deposits.
     */
    public function index(Request $request): View
    {
        $query = PaymentDeposit::with(['user', 'wallet', 'ledgerTransaction'])
            ->latest('created_at');

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter: Currency
        if ($request->filled('currency')) {
            $query->where('currency', $request->input('currency'));
        }

        // Filter: Provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        // Filter: User
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter: Search (deposit number or provider transaction id)
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sq) use ($q) {
                $sq->where('deposit_number', 'like', "%{$q}%")
                    ->orWhere('provider_transaction_id', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($uq) use ($q) {
                        $uq->where('email', 'like', "%{$q}%")
                            ->orWhere('name', 'like', "%{$q}%");
                    });
            });
        }

        // Filter: Date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $deposits = $query->paginate(20)->withQueryString();

        // Summary counts
        $totalSucceeded = PaymentDeposit::where('status', PaymentDepositStatus::SUCCEEDED)->count();
        $totalPending = PaymentDeposit::whereIn('status', [PaymentDepositStatus::PENDING, PaymentDepositStatus::PROCESSING])->count();
        $totalFailed = PaymentDeposit::where('status', PaymentDepositStatus::FAILED)->count();
        $totalRefunded = PaymentDeposit::whereIn('status', [PaymentDepositStatus::PARTIALLY_REFUNDED, PaymentDepositStatus::REFUNDED])->count();

        return view('admin.finance.deposits.index', [
            'deposits' => $deposits,
            'filters' => $request->only(['status', 'currency', 'provider', 'user_id', 'q', 'from_date', 'to_date']),
            'totalSucceeded' => $totalSucceeded,
            'totalPending' => $totalPending,
            'totalFailed' => $totalFailed,
            'totalRefunded' => $totalRefunded,
            'currencies' => Currency::cases(),
            'providers' => PaymentProvider::cases(),
            'statuses' => PaymentDepositStatus::cases(),
        ]);
    }

    /**
     * Show single deposit details with audits and refunds.
     */
    public function show(PaymentDeposit $deposit): View
    {
        $deposit->load(['user.brandProfile', 'wallet', 'ledgerTransaction.entries.account', 'refunds.creator', 'webhooks']);

        return view('admin.finance.deposits.show', [
            'deposit' => $deposit,
        ]);
    }

    /**
     * Display listing of payment refunds.
     */
    public function refunds(Request $request): View
    {
        $query = PaymentRefund::with(['deposit', 'user', 'creator', 'ledgerTransaction'])
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->input('currency'));
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sq) use ($q) {
                $sq->where('refund_number', 'like', "%{$q}%")
                    ->orWhere('provider_refund_id', 'like', "%{$q}%")
                    ->orWhereHas('deposit', function ($dq) use ($q) {
                        $dq->where('deposit_number', 'like', "%{$q}%");
                    });
            });
        }

        $refunds = $query->paginate(20)->withQueryString();

        return view('admin.finance.deposits.refunds', [
            'refunds' => $refunds,
            'filters' => $request->only(['status', 'currency', 'q']),
            'statuses' => PaymentRefundStatus::cases(),
            'currencies' => Currency::cases(),
        ]);
    }

    /**
     * Process refund for a deposit.
     */
    public function storeRefund(Request $request, PaymentDeposit $deposit): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $refund = $this->depositService->processRefund([
                'deposit' => $deposit,
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
                'performer' => $request->user(),
            ]);

            return redirect()->route('admin.finance.deposits.show', $deposit)
                ->with('status', "تم تنفيذ الاسترداد المالي بنجاح برقم [{$refund->refund_number}] وترحيل القيد المحاسبي.");
        } catch (\Throwable $e) {
            return redirect()->route('admin.finance.deposits.show', $deposit)
                ->with('error', 'تعذر تنفيذ الاسترداد: ' . $e->getMessage());
        }
    }

    /**
     * Run on-demand reconciliation for a deposit with the provider API.
     */
    public function reconcile(PaymentDeposit $deposit): RedirectResponse
    {
        try {
            $result = $this->depositService->reconcileWithProvider($deposit);

            if ($result['is_matched']) {
                return redirect()->route('admin.finance.deposits.show', $deposit)
                    ->with('status', 'تمت المطابقة بنجاح: سجلات النظام الداخلي متطابقة 100% مع بيانات مزود الدفع.');
            }

            return redirect()->route('admin.finance.deposits.show', $deposit)
                ->with('warning', 'تمت المطابقة مع المزود وتم رصد فروقات تتطلب المراجعة اليدوية (راجع التقرير).');
        } catch (\Throwable $e) {
            return redirect()->route('admin.finance.deposits.show', $deposit)
                ->with('error', 'تعذر تشغيل المطابقة: ' . $e->getMessage());
        }
    }

    /**
     * Financial Reconciliation Dashboard / Report.
     */
    public function reconciliation(Request $request): View
    {
        // Find deposits requiring attention:
        // 1. Pending deposits older than 30 minutes
        $pendingDeposits = PaymentDeposit::whereIn('status', [PaymentDepositStatus::PENDING, PaymentDepositStatus::PROCESSING])
            ->where('created_at', '<', now()->subMinutes(30))
            ->with(['user', 'wallet'])
            ->latest()
            ->take(20)
            ->get();

        // 2. Succeeded deposits without linked ledger transaction (mismatch risk)
        $unlinkedDeposits = PaymentDeposit::where('status', PaymentDepositStatus::SUCCEEDED)
            ->whereNull('ledger_transaction_id')
            ->with(['user'])
            ->get();

        // 3. Recent failed webhooks
        $failedWebhooks = \App\Models\PaymentWebhook::where('status', 'FAILED')
            ->latest()
            ->take(20)
            ->get();

        // 4. Recent successful deposits
        $recentSucceeded = PaymentDeposit::where('status', PaymentDepositStatus::SUCCEEDED)
            ->with(['user', 'ledgerTransaction'])
            ->latest('paid_at')
            ->take(15)
            ->get();

        return view('admin.finance.deposits.reconciliation', [
            'pendingDeposits' => $pendingDeposits,
            'unlinkedDeposits' => $unlinkedDeposits,
            'failedWebhooks' => $failedWebhooks,
            'recentSucceeded' => $recentSucceeded,
        ]);
    }
}
