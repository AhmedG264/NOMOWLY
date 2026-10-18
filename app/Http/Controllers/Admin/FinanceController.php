<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Enums\LedgerTransactionStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\WalletStatus;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\LedgerTransaction;
use App\Models\Wallet;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected WalletService $walletService
    ) {}

    /**
     * Financial Overview Dashboard
     */
    public function index(Request $request): View
    {
        $selectedCurrency = $request->query('currency', Currency::SAR->value);
        if (!in_array($selectedCurrency, Currency::values(), true)) {
            $selectedCurrency = Currency::SAR->value;
        }

        $trialBalance = $this->ledgerService->getTrialBalance();

        // Calculate currency specific totals
        $currencyWallets = Wallet::where('currency', $selectedCurrency)->get();
        $totalAvailable = '0.0000';
        $totalReserved = '0.0000';

        foreach ($currencyWallets as $w) {
            $totalAvailable = MoneyMath::add($totalAvailable, $w->available_balance ?? '0');
            $totalReserved = MoneyMath::add($totalReserved, $w->reserved_balance ?? '0');
        }

        $totalBalance = MoneyMath::add($totalAvailable, $totalReserved);

        // Recent ledger transactions
        $recentTransactions = LedgerTransaction::with(['creator', 'entries.account', 'entries.wallet.user'])
            ->latest('posted_at')
            ->take(10)
            ->get();

        $stats = [
            'total_wallets' => Wallet::count(),
            'active_wallets' => Wallet::where('status', WalletStatus::ACTIVE)->count(),
            'total_transactions' => LedgerTransaction::count(),
            'selected_currency' => $selectedCurrency,
            'total_available' => $totalAvailable,
            'total_reserved' => $totalReserved,
            'total_balance' => $totalBalance,
            'is_system_balanced' => collect($trialBalance)->every(fn($item) => $item['is_balanced']),
        ];

        return view('admin.finance.index', [
            'stats' => $stats,
            'trialBalance' => $trialBalance,
            'recentTransactions' => $recentTransactions,
            'currencies' => Currency::cases(),
            'selectedCurrency' => $selectedCurrency,
        ]);
    }

    /**
     * List all wallets with filtering and search
     */
    public function wallets(Request $request): View
    {
        $query = Wallet::with(['user.brandProfile', 'user.marketerProfile']);

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('type')) {
            $query->where('wallet_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->whereHas('user', function ($uq) use ($search) {
                $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $wallets = $query->latest('updated_at')->paginate(20)->withQueryString();

        return view('admin.finance.wallets', [
            'wallets' => $wallets,
            'currencies' => Currency::cases(),
            'walletTypes' => WalletType::cases(),
            'walletStatuses' => WalletStatus::cases(),
            'filters' => $request->only(['currency', 'type', 'status', 'q']),
        ]);
    }

    /**
     * View wallet detail and statement
     */
    public function walletShow(Wallet $wallet): View
    {
        $wallet->load(['user.brandProfile', 'user.marketerProfile']);
        $reconciliation = $this->walletService->reconcileWallet($wallet);

        $transactions = $wallet->transactions()
            ->with(['ledgerTransaction', 'ledgerEntry.account'])
            ->latest('created_at')
            ->paginate(25);

        return view('admin.finance.wallet-show', [
            'wallet' => $wallet,
            'reconciliation' => $reconciliation,
            'transactions' => $transactions,
        ]);
    }

    /**
     * List all ledger transactions (Journal)
     */
    public function transactions(Request $request): View
    {
        $query = LedgerTransaction::with(['creator', 'entries.account', 'entries.wallet.user']);

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($sq) use ($search) {
                $sq->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('idempotency_key', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest('posted_at')->paginate(25)->withQueryString();

        return view('admin.finance.transactions', [
            'transactions' => $transactions,
            'currencies' => Currency::cases(),
            'transactionTypes' => LedgerTransactionType::cases(),
            'transactionStatuses' => LedgerTransactionStatus::cases(),
            'filters' => $request->only(['currency', 'type', 'status', 'q']),
        ]);
    }

    /**
     * View transaction details and double-entry line items
     */
    public function transactionShow(LedgerTransaction $transaction): View
    {
        $transaction->load([
            'creator',
            'reversedTransaction',
            'reversal',
            'entries.account',
            'entries.wallet.user',
        ]);

        $totalDebits = '0.0000';
        $totalCredits = '0.0000';

        foreach ($transaction->entries as $entry) {
            if ($entry->isDebit()) {
                $totalDebits = MoneyMath::add($totalDebits, $entry->amount);
            } else {
                $totalCredits = MoneyMath::add($totalCredits, $entry->amount);
            }
        }

        $isBalanced = MoneyMath::comp($totalDebits, $totalCredits) === 0;

        return view('admin.finance.transaction-show', [
            'transaction' => $transaction,
            'totalDebits' => $totalDebits,
            'totalCredits' => $totalCredits,
            'isBalanced' => $isBalanced,
        ]);
    }

    /**
     * Reverse a posted transaction (Storno / Contra entry)
     */
    public function reverse(Request $request, LedgerTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ], [
            'reason.required' => 'يجب ذكر سبب واضح ومحدد لعملية القيد العكسي.',
            'reason.min' => 'سبب القيد العكسي يجب أن يتضمن 5 أحرف على الأقل.',
        ]);

        try {
            $reversalTx = $this->ledgerService->reverseTransaction(
                $transaction,
                $validated['reason'],
                $request->user()
            );

            return redirect()->route('admin.finance.transactions.show', $transaction)
                ->with('status', "تم عكس المعاملة بنجاح وإنشاء القيد التصحيحي رقم [{$reversalTx->transaction_number}].");
        } catch (\Throwable $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }
    }

    /**
     * View Trial Balance & Integrity Check
     */
    public function trialBalance(Request $request): View
    {
        $trialBalance = $this->ledgerService->getTrialBalance();

        return view('admin.finance.trial-balance', [
            'trialBalance' => $trialBalance,
            'currencies' => Currency::cases(),
        ]);
    }
}
