<?php

namespace App\Http\Controllers\Marketer;

use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\PayoutRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CampaignApplication;
use App\Models\CommissionSettlement;
use App\Models\Conversion;
use App\Models\PayoutRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Ledger\PayoutService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected PayoutService $payoutService
    ) {}

    /**
     * Display marketer's multi-currency wallets, settlements, statement, and payout requests.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $marketerProfile = $user->marketerProfile;
        abort_unless($marketerProfile, 403, 'الملف الشخصي للمسوق غير مكتمل.');

        // Fetch or initialize wallets for all 8 supported currencies
        $currencies = Currency::cases();
        $wallets = [];
        $reconciliations = [];

        foreach ($currencies as $curr) {
            $w = $this->walletService->getOrCreateWallet($user, $curr);
            $wallets[$curr->value] = $w;
            $reconciliations[$curr->value] = $this->walletService->reconcileWallet($w);
        }

        // Active selected currency (default to SAR)
        $selectedCurrency = $request->input('currency', 'SAR');
        if (!Currency::tryFrom($selectedCurrency)) {
            $selectedCurrency = 'SAR';
        }
        $activeWallet = $wallets[$selectedCurrency] ?? $wallets['SAR'];

        // Marketer application IDs for conversions matching
        $applicationIds = CampaignApplication::where('marketer_profile_id', $marketerProfile->id)->pluck('id');

        // Real financial metrics calculated strictly from database for the selected currency
        $approvedCommissionSum = (string) Conversion::whereIn('campaign_application_id', $applicationIds)
            ->where('currency', $selectedCurrency)
            ->where('status', ConversionStatus::APPROVED)
            ->sum('commission_value');

        $pendingCommissionSum = (string) Conversion::whereIn('campaign_application_id', $applicationIds)
            ->where('currency', $selectedCurrency)
            ->where('status', ConversionStatus::PENDING)
            ->sum('commission_value');

        $totalActuallyPaidOut = (string) PayoutRequest::where('user_id', $user->id)
            ->where('currency', $selectedCurrency)
            ->where('status', PayoutRequestStatus::PAID)
            ->sum('amount');

        // Commissions actually credited and settled to wallet
        $settledCommissionSum = (string) CommissionSettlement::whereHas('conversion.campaignApplication', function ($q) use ($marketerProfile) {
            $q->where('marketer_profile_id', $marketerProfile->id);
        })
            ->where('currency', $selectedCurrency)
            ->whereNull('reversed_at')
            ->sum('commission_amount');

        // Earnings breakdown grouped by campaign in the selected currency
        $campaignEarningsRaw = CommissionSettlement::whereHas('conversion.campaignApplication', function ($q) use ($marketerProfile) {
            $q->where('marketer_profile_id', $marketerProfile->id);
        })
            ->where('currency', $selectedCurrency)
            ->whereNull('reversed_at')
            ->with(['conversion.campaign.brandProfile'])
            ->get();

        $campaignEarnings = $campaignEarningsRaw->groupBy(function ($settlement) {
            return $settlement->conversion?->campaign_id ?? 'unknown';
        })->map(function ($items, $campaignId) use ($selectedCurrency) {
            $first = $items->first();
            $campaign = $first->conversion?->campaign;
            $totalEarned = '0.0000';
            foreach ($items as $item) {
                $totalEarned = MoneyMath::add($totalEarned, $item->commission_amount);
            }
            return [
                'campaign_id' => $campaignId,
                'campaign_slug' => $campaign?->slug,
                'campaign_title' => $campaign?->title ?? 'حملة عامة',
                'company_name' => $campaign?->brandProfile?->company_name ?? '—',
                'settled_count' => $items->count(),
                'total_earned' => $totalEarned,
                'currency' => $selectedCurrency,
            ];
        })->values();

        // Payout Requests for this marketer with pagination
        $payoutRequests = PayoutRequest::where('user_id', $user->id)
            ->with(['reviewer', 'payer', 'wallet'])
            ->latest('created_at')
            ->paginate(10, ['*'], 'payouts_page')
            ->withQueryString();

        // Commission Settlements earned by this marketer with pagination
        $settlements = CommissionSettlement::whereHas('conversion.campaignApplication', function ($q) use ($marketerProfile) {
            $q->where('marketer_profile_id', $marketerProfile->id);
        })
            ->with(['conversion.campaign.brandProfile', 'ledgerTransaction'])
            ->latest('settled_at')
            ->paginate(10, ['*'], 'settlements_page')
            ->withQueryString();

        // Statement of movements (WalletTransaction) for this marketer
        $walletIds = collect($wallets)->pluck('id')->all();
        $statementQuery = WalletTransaction::with(['ledgerTransaction.entries.account', 'ledgerEntry'])
            ->whereIn('wallet_id', $walletIds);

        // Filter by currency
        if ($request->filled('currency')) {
            $statementQuery->where('currency', $request->input('currency'));
        }

        // Filter by transaction type via ledgerTransaction
        if ($request->filled('transaction_type')) {
            $type = $request->input('transaction_type');
            $statementQuery->whereHas('ledgerTransaction', function ($q) use ($type) {
                $q->where('transaction_type', $type);
            });
        }

        // Filter by transaction status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $statementQuery->whereHas('ledgerTransaction', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $statementQuery->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $statementQuery->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Search by transaction number or description
        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $statementQuery->where(function ($sq) use ($search) {
                $sq->where('description', 'like', "%{$search}%")
                    ->orWhereHas('ledgerTransaction', function ($tq) use ($search) {
                        $tq->where('transaction_number', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $statementQuery->latest('created_at')
            ->paginate(15, ['*'], 'statement_page')
            ->withQueryString();

        return view('marketer.wallet.index', [
            'wallets' => $wallets,
            'activeWallet' => $activeWallet,
            'selectedCurrency' => $selectedCurrency,
            'currencies' => $currencies,
            'payoutRequests' => $payoutRequests,
            'settlements' => $settlements,
            'campaignEarnings' => $campaignEarnings,
            'transactions' => $transactions,
            'approvedCommissionSum' => MoneyMath::format($approvedCommissionSum),
            'pendingCommissionSum' => MoneyMath::format($pendingCommissionSum),
            'settledCommissionSum' => MoneyMath::format($settledCommissionSum),
            'totalActuallyPaidOut' => MoneyMath::format($totalActuallyPaidOut),
            'reconciliation' => $reconciliations[$selectedCurrency] ?? null,
            'filters' => $request->only(['currency', 'transaction_type', 'status', 'from_date', 'to_date', 'q']),
        ]);
    }

    /**
     * Submit a payout request from a specific currency wallet.
     */
    public function storePayoutRequest(Request $request): RedirectResponse
    {
        $user = $request->user();
        $marketerProfile = $user->marketerProfile;
        abort_unless($marketerProfile, 403, 'الملف الشخصي للمسوق غير مكتمل.');

        $validated = $request->validate([
            'wallet_id' => ['required', 'uuid', 'exists:wallets,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'bank_name' => ['required', 'string', 'min:3', 'max:100'],
            'account_holder_name' => ['required', 'string', 'min:3', 'max:120'],
            'account_reference' => ['required', 'string', 'min:8', 'max:50'],
        ], [
            'wallet_id.required' => 'يرجى اختيار المحفظة المراد سحب الأرباح منها.',
            'amount.required' => 'يرجى تحديد المبلغ المراد سحبه.',
            'amount.gt' => 'يجب أن يكون مبلغ السحب أكبر من صفر.',
            'bank_name.required' => 'اسم البنك إلزامي لإتمام التحويل.',
            'account_holder_name.required' => 'اسم صاحب الحساب البنكي إلزامي.',
            'account_reference.required' => 'رقم الآيبان (IBAN) أو الحساب البنكي إلزامي.',
            'account_reference.min' => 'رقم الحساب أو الآيبان قصير جداً.',
        ]);

        $wallet = Wallet::where('id', $validated['wallet_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $amountStr = MoneyMath::format($validated['amount']);

        try {
            $payoutRequest = $this->payoutService->createPayoutRequest(
                $user,
                $wallet,
                $amountStr,
                [
                    'bank_name' => trim($validated['bank_name']),
                    'account_holder_name' => trim($validated['account_holder_name']),
                    'account_reference' => trim($validated['account_reference']),
                    'idempotency_key' => 'payout-req-' . $user->id . '-' . Str::random(16),
                ]
            );

            return redirect()->route('marketer.wallet.index')->with(
                'status',
                "تم تقديم طلب سحب الأرباح رقم [{$payoutRequest->request_number}] بمبلغ {$amountStr} {$wallet->currency->value} بنجاح، وتم حجز المبلغ بانتظار المراجعة والتحويل البنكي."
            );
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'amount' => $e->getMessage(),
            ]);
        }
    }
}
