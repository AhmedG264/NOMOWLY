<?php

namespace App\Http\Controllers\Brand;

use App\Enums\Currency;
use App\Enums\LedgerTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Display Brand multi-currency wallets, escrow budget reserves, and statement of movements.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $brandProfile = $user->brandProfile;
        abort_unless($brandProfile, 403, 'الملف التجاري غير مكتمل.');

        $currencies = Currency::cases();
        $wallets = [];
        $reconciliations = [];

        foreach ($currencies as $curr) {
            $w = $this->walletService->getOrCreateWallet($user, $curr);
            $wallets[$curr->value] = $w;
            $reconciliations[$curr->value] = $this->walletService->reconcileWallet($w);
        }

        // Active selected currency tab (default to brand primary currency or SAR)
        $defaultCurrency = $brandProfile->currency ?? 'SAR';
        $selectedCurrency = $request->input('currency', $defaultCurrency);
        if (!Currency::tryFrom($selectedCurrency)) {
            $selectedCurrency = 'SAR';
        }

        $activeWallet = $wallets[$selectedCurrency] ?? $wallets['SAR'];

        // Brand campaigns summary
        $campaigns = $brandProfile->campaigns()->select('id', 'title', 'status')->get();
        $campaignsMap = $campaigns->keyBy('id');

        // Query statement of movements (WalletTransaction) strictly scoped to user's wallets
        $walletIds = collect($wallets)->pluck('id')->all();

        $statementQuery = WalletTransaction::with(['ledgerTransaction.entries.account', 'ledgerEntry'])
            ->whereIn('wallet_id', $walletIds);

        // Filter by currency
        if ($request->filled('currency')) {
            $statementQuery->where('currency', $request->input('currency'));
        }

        // Filter by transaction type via ledgerTransaction relation
        if ($request->filled('transaction_type')) {
            $type = $request->input('transaction_type');
            $statementQuery->whereHas('ledgerTransaction', function ($q) use ($type) {
                $q->where('transaction_type', $type);
            });
        }

        // Filter by transaction status (POSTED, REVERSED)
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

        $transactions = $statementQuery->latest('created_at')->paginate(20)->withQueryString();

        // Resolve conversions linked to any transactions for campaign title mapping
        $conversionIds = $transactions->map(function ($tx) {
            if ($tx->ledgerTransaction?->source_type === \App\Models\Conversion::class) {
                return $tx->ledgerTransaction->source_id;
            }
            return null;
        })->filter()->unique()->all();

        $conversionsMap = !empty($conversionIds)
            ? \App\Models\Conversion::with('campaign')->whereIn('id', $conversionIds)->get()->keyBy('id')
            : collect();

        // Calculate Escrow reserves breakdown across brand's campaigns
        $escrowHoldTransactions = WalletTransaction::with('ledgerTransaction')
            ->where('wallet_id', $activeWallet->id)
            ->whereHas('ledgerTransaction', function ($q) {
                $q->whereIn('transaction_type', [
                    LedgerTransactionType::ESCROW_HOLD,
                    LedgerTransactionType::ESCROW_RELEASE,
                    LedgerTransactionType::COMMISSION_EARNING,
                ]);
            })
            ->latest('created_at')
            ->take(10)
            ->get();

        // Phase 6: Brand Deposits history and gateway availability
        $deposits = \App\Models\PaymentDeposit::where('user_id', $user->id)
            ->where('currency', $selectedCurrency)
            ->latest('created_at')
            ->take(15)
            ->get();

        /** @var \App\Services\Payment\PaymentGatewayManager $gatewayManager */
        $gatewayManager = app(\App\Services\Payment\PaymentGatewayManager::class);
        $availableGateways = $gatewayManager->getAvailableGateways($selectedCurrency);
        $isPaymentGatewayAvailable = !empty($availableGateways);

        return view('brand.wallet.index', [
            'wallets' => $wallets,
            'activeWallet' => $activeWallet,
            'selectedCurrency' => $selectedCurrency,
            'currencies' => $currencies,
            'transactions' => $transactions,
            'campaigns' => $campaigns,
            'campaignsMap' => $campaignsMap,
            'conversionsMap' => $conversionsMap,
            'escrowHoldTransactions' => $escrowHoldTransactions,
            'deposits' => $deposits,
            'availableGateways' => $availableGateways,
            'isPaymentGatewayAvailable' => $isPaymentGatewayAvailable,
            'reconciliation' => $reconciliations[$selectedCurrency] ?? null,
            'filters' => $request->only(['currency', 'transaction_type', 'status', 'from_date', 'to_date', 'q']),
        ]);
    }
}
