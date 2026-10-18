<?php

namespace App\Services\Ledger;

use App\Enums\AuditAction;
use App\Enums\Currency;
use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionStatus;
use App\Enums\LedgerTransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\UnbalancedLedgerTransactionException;
use App\Models\Campaign;
use App\Models\Conversion;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AuditService;
use App\Services\Math\MoneyMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class LedgerService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Post a balanced double-entry financial transaction atomically.
     *
     * @param array{
     *     currency: string|Currency,
     *     transaction_type: string|LedgerTransactionType,
     *     idempotency_key: string,
     *     description: string,
     *     entries: list<array{
     *         account_code: string,
     *         wallet_id?: ?string,
     *         entry_type: string|LedgerEntryType,
     *         amount: string|int,
     *         description?: ?string
     *     }>,
     *     source_type?: ?string,
     *     source_id?: ?string,
     *     metadata?: ?array,
     *     created_by?: ?User
     * } $data
     */
    public function postTransaction(array $data): LedgerTransaction
    {
        $idempotencyKey = trim((string) ($data['idempotency_key'] ?? ''));
        if (empty($idempotencyKey)) {
            throw new InvalidArgumentException('مفتاح عدم التكرار (Idempotency Key) إلزامي لكافة المعاملات المالية.');
        }

        // Idempotency check: Return existing transaction if already posted
        $existing = LedgerTransaction::where('idempotency_key', $idempotencyKey)
            ->with(['entries.account', 'entries.wallet'])
            ->first();

        if ($existing) {
            return $existing;
        }

        $currency = $data['currency'] instanceof Currency ? $data['currency'] : Currency::from(strtoupper(trim($data['currency'])));
        $currencyCode = $currency->value;

        $txType = $data['transaction_type'] instanceof LedgerTransactionType
            ? $data['transaction_type']
            : LedgerTransactionType::from(strtoupper(trim($data['transaction_type'])));

        $entriesData = $data['entries'] ?? [];
        if (count($entriesData) < 2) {
            throw new UnbalancedLedgerTransactionException('يجب أن تحتوي المعاملة المالية على قيدين محاسبيين على الأقل (مدين ودائن).');
        }

        // Compute total debits and credits with strict BCMath (no float!)
        $totalDebits = '0.0000';
        $totalCredits = '0.0000';

        $preparedEntries = [];

        foreach ($entriesData as $entryItem) {
            $amount = MoneyMath::format($entryItem['amount']);

            if (!MoneyMath::isPositive($amount)) {
                throw new InvalidArgumentException("قيمة القيد المحاسبي يجب أن تكون موجبة وأكبر من صفر: [{$amount}]");
            }

            $entryType = $entryItem['entry_type'] instanceof LedgerEntryType
                ? $entryItem['entry_type']
                : LedgerEntryType::from(strtoupper(trim($entryItem['entry_type'])));

            if ($entryType === LedgerEntryType::DEBIT) {
                $totalDebits = MoneyMath::add($totalDebits, $amount);
            } else {
                $totalCredits = MoneyMath::add($totalCredits, $amount);
            }

            $preparedEntries[] = [
                'account_code' => trim($entryItem['account_code']),
                'wallet_id' => $entryItem['wallet_id'] ?? null,
                'entry_type' => $entryType,
                'amount' => $amount,
                'description' => $entryItem['description'] ?? null,
            ];
        }

        // Double-entry validation: sum(debit) MUST equal sum(credit)
        if (MoneyMath::comp($totalDebits, $totalCredits) !== 0) {
            throw new UnbalancedLedgerTransactionException(
                "القيود المحاسبية غير متوازنة بالعملة [{$currencyCode}]: مجموع المدين [{$totalDebits}] لا يساوي مجموع الدائن [{$totalCredits}]."
            );
        }

        // Execute atomically with row locking inside DB transaction
        return DB::transaction(function () use (
            $idempotencyKey,
            $currencyCode,
            $txType,
            $data,
            $preparedEntries,
            $totalDebits
        ) {
            // Double check idempotency under transaction lock
            $existing = LedgerTransaction::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            // Generate human-friendly sequential transaction number
            $txNumber = 'TX-' . date('Ymd') . '-' . strtoupper(Str::random(8));

            $transaction = LedgerTransaction::create([
                'transaction_number' => $txNumber,
                'currency' => $currencyCode,
                'transaction_type' => $txType,
                'status' => LedgerTransactionStatus::POSTED,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => isset($data['created_by']) ? $data['created_by']?->id : null,
                'posted_at' => now(),
            ]);

            // Cache accounts to minimize queries
            $accountCodes = array_unique(array_column($preparedEntries, 'account_code'));
            $accounts = LedgerAccount::whereIn('code', $accountCodes)->get()->keyBy('code');

            foreach ($accountCodes as $code) {
                if (!isset($accounts[$code])) {
                    throw new InvalidArgumentException("الحساب المحاسبي غير معرف في دليل الحسابات: [{$code}]");
                }
            }

            // Process entries and update wallets with pessimistic locks
            foreach ($preparedEntries as $entry) {
                $account = $accounts[$entry['account_code']];
                $wallet = null;

                if (!empty($entry['wallet_id'])) {
                    // Lock wallet row for update
                    $wallet = Wallet::where('id', $entry['wallet_id'])->lockForUpdate()->first();

                    if (!$wallet) {
                        throw new InvalidArgumentException("المحفظة المحددة غير موجودة: [{$entry['wallet_id']}]");
                    }

                    // Strict currency match
                    if ($wallet->currency->value !== $currencyCode) {
                        throw new InvalidArgumentException(
                            "عملة المحفظة [{$wallet->currency->value}] لا تطابق عملة المعاملة المحاسبية [{$currencyCode}]."
                        );
                    }

                    if (!$wallet->isActive()) {
                        throw new RuntimeException("المحفظة [{$wallet->id}] غير نشطة أو مجمدة ولا يمكن إجراء قيود عليها.");
                    }
                }

                // Create the immutable ledger entry
                $ledgerEntry = LedgerEntry::create([
                    'ledger_transaction_id' => $transaction->id,
                    'ledger_account_id' => $account->id,
                    'wallet_id' => $wallet?->id,
                    'entry_type' => $entry['entry_type'],
                    'amount' => $entry['amount'],
                    'currency' => $currencyCode,
                    'description' => $entry['description'] ?? $transaction->description,
                ]);

                // Update wallet balances according to account nature
                if ($wallet) {
                    $this->applyWalletEntryImpact($wallet, $account, $entry['entry_type'], $entry['amount'], $transaction, $ledgerEntry);
                }
            }

            // Record in audit log
            AuditService::log(
                action: AuditAction::LEDGER_TRANSACTION_POSTED,
                userId: isset($data['created_by']) ? $data['created_by']?->id : null,
                entityType: LedgerTransaction::class,
                entityId: $transaction->id,
                payloadDiff: [
                    'new' => [
                        'transaction_number' => $transaction->transaction_number,
                        'currency' => $currencyCode,
                        'type' => $txType->value,
                        'total_amount' => $totalDebits,
                        'entries_count' => count($preparedEntries),
                        'idempotency_key' => $idempotencyKey,
                    ],
                ],
                status: 'SUCCESS',
                reason: $data['description'] ?? 'ترحيل معاملة مالية متوازنة في دفتر القيود'
            );

            return $transaction->load(['entries.account', 'entries.wallet']);
        });
    }

    /**
     * Apply the double-entry accounting impact to the specific wallet.
     * Prevents negative balances and records an immutable wallet statement entry.
     */
    protected function applyWalletEntryImpact(
        Wallet $wallet,
        LedgerAccount $account,
        LedgerEntryType $entryType,
        string $amount,
        LedgerTransaction $transaction,
        LedgerEntry $ledgerEntry
    ): void {
        $currentAvailable = MoneyMath::format($wallet->available_balance);
        $currentReserved = MoneyMath::format($wallet->reserved_balance);

        $newAvailable = $currentAvailable;
        $newReserved = $currentReserved;

        // Customer Wallets and Payable are LIABILITIES from the platform perspective:
        // 1. BRAND WALLET LIABILITY (2010): Available balance
        if ($account->code === '2010_BRAND_WALLET_LIABILITY') {
            if ($entryType === LedgerEntryType::CREDIT) {
                // Deposit / Refund: Increases customer balance
                $newAvailable = MoneyMath::add($currentAvailable, $amount);
            } else {
                // Withdrawal / Spend: Decreases customer balance
                if (MoneyMath::comp($currentAvailable, $amount) < 0) {
                    throw new InsufficientFundsException(
                        "الرصيد المتاح في المحفظة [{$currentAvailable} {$wallet->currency->value}] غير كافٍ للخصم [{$amount} {$wallet->currency->value}]."
                    );
                }
                $newAvailable = MoneyMath::sub($currentAvailable, $amount);
            }
        }
        // 2. CAMPAIGN ESCROW LIABILITY (2020): Reserved balance
        elseif ($account->code === '2020_CAMPAIGN_ESCROW_LIABILITY') {
            if ($entryType === LedgerEntryType::CREDIT) {
                // Budget reserved into escrow: Increases reserved balance
                $newReserved = MoneyMath::add($currentReserved, $amount);
            } else {
                // Budget released or disbursed: Decreases reserved balance
                if (MoneyMath::comp($currentReserved, $amount) < 0) {
                    throw new InsufficientFundsException(
                        "الرصيد المحجوز في المحفظة [{$currentReserved} {$wallet->currency->value}] أقل من المبلغ المطلوب تحريره [{$amount} {$wallet->currency->value}]."
                    );
                }
                $newReserved = MoneyMath::sub($currentReserved, $amount);
            }
        }
        // 3. MARKETER PAYABLE LIABILITY (2030): Marketer earned balance
        elseif ($account->code === '2030_MARKETER_PAYABLE_LIABILITY') {
            if ($entryType === LedgerEntryType::CREDIT) {
                // Commission earned: Increases available balance
                $newAvailable = MoneyMath::add($currentAvailable, $amount);
            } else {
                // Payout / Withdrawal: if it's a withdrawal, it fulfills a held payout request from reserved_balance
                if ($transaction->transaction_type === LedgerTransactionType::WITHDRAWAL) {
                    if (MoneyMath::comp($currentReserved, $amount) < 0) {
                        throw new InsufficientFundsException(
                            "رصيد العمولات المحجوز للسحب للمسوق [{$currentReserved} {$wallet->currency->value}] أقل من المبلغ المصروف [{$amount} {$wallet->currency->value}]."
                        );
                    }
                    $newReserved = MoneyMath::sub($currentReserved, $amount);
                } else {
                    // Reversal / Clawback: Decreases available balance
                    if (MoneyMath::comp($currentAvailable, $amount) < 0) {
                        throw new InsufficientFundsException(
                            "رصيد العمولات المتاح للمسوق [{$currentAvailable} {$wallet->currency->value}] غير كافٍ لإتمام السحب [{$amount} {$wallet->currency->value}]."
                        );
                    }
                    $newAvailable = MoneyMath::sub($currentAvailable, $amount);
                }
            }
        }

        // Save wallet balances
        $wallet->update([
            'available_balance' => $newAvailable,
            'reserved_balance' => $newReserved,
        ]);

        // Create immutable wallet statement transaction record
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'ledger_transaction_id' => $transaction->id,
            'ledger_entry_id' => $ledgerEntry->id,
            'entry_type' => $entryType,
            'amount' => $amount,
            'balance_after' => $newAvailable,
            'reserved_after' => $newReserved,
            'currency' => $wallet->currency->value,
            'description' => $ledgerEntry->description ?? $transaction->description,
        ]);
    }

    /**
     * Reverse a posted transaction via an offsetting (Storno) transaction.
     * The original posted transaction and entries remain completely immutable.
     */
    public function reverseTransaction(
        LedgerTransaction $transaction,
        string $reason,
        User $performedBy
    ): LedgerTransaction {
        if ($transaction->status !== LedgerTransactionStatus::POSTED) {
            throw new RuntimeException("لا يمكن عكس هذه المعاملة لأنها بحالة [{$transaction->status->value}] وليست مرحلة.");
        }

        if ($transaction->reversal()->exists()) {
            throw new RuntimeException("تم عكس هذه المعاملة مسبقاً بقيد تصحيحي.");
        }

        $currency = $transaction->currency;
        $originalEntries = $transaction->entries()->with(['account', 'wallet'])->get();

        // Build reversal entries with inverted debit/credit
        $reversalEntries = [];
        foreach ($originalEntries as $entry) {
            $invertedType = $entry->entry_type === LedgerEntryType::DEBIT
                ? LedgerEntryType::CREDIT
                : LedgerEntryType::DEBIT;

            $reversalEntries[] = [
                'account_code' => $entry->account->code,
                'wallet_id' => $entry->wallet_id,
                'entry_type' => $invertedType,
                'amount' => $entry->amount,
                'description' => "قيد عكسي للمعاملة [{$transaction->transaction_number}]: {$reason}",
            ];
        }

        $reversalIdempotencyKey = "reversal-{$transaction->id}";

        return DB::transaction(function () use (
            $transaction,
            $currency,
            $reversalIdempotencyKey,
            $reversalEntries,
            $reason,
            $performedBy
        ) {
            /** @var LedgerTransaction $lockedTx */
            $lockedTx = LedgerTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();

            if ($lockedTx->status !== LedgerTransactionStatus::POSTED || $lockedTx->reversal()->exists()) {
                throw new RuntimeException("تم عكس هذه المعاملة مسبقاً بقيد تصحيحي.");
            }

            $reversalTx = $this->postTransaction([
                'currency' => $currency,
                'transaction_type' => LedgerTransactionType::REVERSAL,
                'idempotency_key' => $reversalIdempotencyKey,
                'description' => "قيد عكسي تصحيحي للمعاملة رقم [{$transaction->transaction_number}]: {$reason}",
                'entries' => $reversalEntries,
                'source_type' => LedgerTransaction::class,
                'source_id' => $transaction->id,
                'created_by' => $performedBy,
                'metadata' => [
                    'original_transaction_number' => $transaction->transaction_number,
                    'original_transaction_id' => $transaction->id,
                    'reversal_reason' => $reason,
                ],
            ]);

            // Mark original transaction as reversed
            $transaction->update([
                'status' => LedgerTransactionStatus::REVERSED,
                'reversed_transaction_id' => $reversalTx->id,
                'reversal_reason' => $reason,
            ]);

            AuditService::log(
                action: AuditAction::LEDGER_TRANSACTION_REVERSED,
                userId: $performedBy->id,
                entityType: LedgerTransaction::class,
                entityId: $transaction->id,
                payloadDiff: [
                    'old' => ['status' => LedgerTransactionStatus::POSTED->value],
                    'new' => [
                        'status' => LedgerTransactionStatus::REVERSED->value,
                        'reversal_transaction_id' => $reversalTx->id,
                        'reversal_reason' => $reason,
                    ],
                ],
                status: 'SUCCESS',
                reason: $reason
            );

            return $reversalTx;
        });
    }

    /**
     * Reserve campaign budget in escrow from brand wallet.
     * Accounting:
     *   DEBIT: 2010_BRAND_WALLET_LIABILITY (reduces available balance)
     *   CREDIT: 2020_CAMPAIGN_ESCROW_LIABILITY (increases reserved balance)
     */
    public function reserveCampaignBudget(
        Campaign $campaign,
        string|int $amount,
        string|Currency $currency,
        User $brandUser,
        ?string $idempotencyKey = null
    ): LedgerTransaction {
        $currencyCode = $currency instanceof Currency ? $currency->value : strtoupper(trim($currency));
        $normAmount = MoneyMath::format($amount);

        $wallet = $this->walletService->getOrCreateWallet($brandUser, $currencyCode);

        $idempotency = $idempotencyKey ?: "campaign-reserve-{$campaign->id}-" . Str::random(10);

        $tx = $this->postTransaction([
            'currency' => $currencyCode,
            'transaction_type' => LedgerTransactionType::ESCROW_HOLD,
            'idempotency_key' => $idempotency,
            'description' => "حجز ميزانية بالضمان للحملة: {$campaign->title}",
            'source_type' => Campaign::class,
            'source_id' => $campaign->id,
            'created_by' => $brandUser,
            'entries' => [
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => $normAmount,
                    'description' => "خصم من الرصيد المتاح لحساب الضمان للحملة: {$campaign->title}",
                ],
                [
                    'account_code' => '2020_CAMPAIGN_ESCROW_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => $normAmount,
                    'description' => "إيداع ميزانية محجوزة بالضمان للحملة: {$campaign->title}",
                ],
            ],
        ]);

        AuditService::log(
            action: AuditAction::BUDGET_RESERVED,
            userId: $brandUser->id,
            entityType: Campaign::class,
            entityId: $campaign->id,
            payloadDiff: [
                'new' => [
                    'campaign_id' => $campaign->id,
                    'amount' => $normAmount,
                    'currency' => $currencyCode,
                    'transaction_id' => $tx->id,
                ],
            ],
            status: 'SUCCESS',
            reason: "حجز ميزانية حملة في المحفظة"
        );

        return $tx;
    }

    /**
     * Release campaign reserved budget back to brand available balance.
     * Accounting:
     *   DEBIT: 2020_CAMPAIGN_ESCROW_LIABILITY (reduces reserved balance)
     *   CREDIT: 2010_BRAND_WALLET_LIABILITY (increases available balance)
     */
    public function releaseCampaignBudget(
        Campaign $campaign,
        string|int $amount,
        string|Currency $currency,
        User $brandUser,
        string $reason,
        ?string $idempotencyKey = null
    ): LedgerTransaction {
        $currencyCode = $currency instanceof Currency ? $currency->value : strtoupper(trim($currency));
        $normAmount = MoneyMath::format($amount);

        $wallet = $this->walletService->getOrCreateWallet($brandUser, $currencyCode);

        $idempotency = $idempotencyKey ?: "campaign-release-{$campaign->id}-" . Str::random(10);

        $tx = $this->postTransaction([
            'currency' => $currencyCode,
            'transaction_type' => LedgerTransactionType::ESCROW_RELEASE,
            'idempotency_key' => $idempotency,
            'description' => "تحرير ميزانية محجوزة للحملة [{$campaign->title}]: {$reason}",
            'source_type' => Campaign::class,
            'source_id' => $campaign->id,
            'created_by' => $brandUser,
            'entries' => [
                [
                    'account_code' => '2020_CAMPAIGN_ESCROW_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => $normAmount,
                    'description' => "خصم من رصيد الضمان المحجوز: {$reason}",
                ],
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => $normAmount,
                    'description' => "إعادة إلى الرصيد المتاح للمحفظة: {$reason}",
                ],
            ],
        ]);

        AuditService::log(
            action: AuditAction::BUDGET_RELEASED,
            userId: $brandUser->id,
            entityType: Campaign::class,
            entityId: $campaign->id,
            payloadDiff: [
                'new' => [
                    'campaign_id' => $campaign->id,
                    'amount' => $normAmount,
                    'currency' => $currencyCode,
                    'transaction_id' => $tx->id,
                ],
            ],
            status: 'SUCCESS',
            reason: $reason
        );

        return $tx;
    }

    /**
     * Prepare a future conversion commission settlement (Double-Entry template).
     * NOTE: Does NOT execute on historical conversions automatically.
     */
    public function settleConversionCommission(
        Conversion $conversion,
        User $authorizer,
        ?string $platformFeeAmount = '0.0000'
    ): LedgerTransaction {
        $currency = $conversion->currency;
        $commissionAmount = MoneyMath::format($conversion->commission_value);
        $feeAmount = MoneyMath::format($platformFeeAmount ?? '0.0000');
        $totalEscrowDeduction = MoneyMath::add($commissionAmount, $feeAmount);

        $campaign = $conversion->campaign;
        $brandUser = $campaign->brandProfile->user;
        $marketerUser = $conversion->campaignApplication->marketerProfile->user;

        $brandWallet = $this->walletService->getOrCreateWallet($brandUser, $currency);
        $marketerWallet = $this->walletService->getOrCreateWallet($marketerUser, $currency);

        $idempotency = "conversion-settlement-{$conversion->id}";

        $entries = [
            // Debit Brand Escrow: funds leave campaign escrow
            [
                'account_code' => '2020_CAMPAIGN_ESCROW_LIABILITY',
                'wallet_id' => $brandWallet->id,
                'entry_type' => LedgerEntryType::DEBIT,
                'amount' => $totalEscrowDeduction,
                'description' => "صرف عمولة مبيعات مستحقة للتحويل رقم {$conversion->id}",
            ],
            // Credit Marketer Payable: funds credited to marketer
            [
                'account_code' => '2030_MARKETER_PAYABLE_LIABILITY',
                'wallet_id' => $marketerWallet->id,
                'entry_type' => LedgerEntryType::CREDIT,
                'amount' => $commissionAmount,
                'description' => "استحقاق عمولة مبيعات مؤكدة من حملة [{$campaign->title}]",
            ],
        ];

        // If platform fee is configured
        if (MoneyMath::isPositive($feeAmount)) {
            $entries[] = [
                'account_code' => '4010_PLATFORM_COMMISSION_REVENUE',
                'entry_type' => LedgerEntryType::CREDIT,
                'amount' => $feeAmount,
                'description' => "إيراد عمولة المنصة عن تحويل مبيعات رقم {$conversion->id}",
            ];
        }

        return $this->postTransaction([
            'currency' => $currency,
            'transaction_type' => LedgerTransactionType::COMMISSION_EARNING,
            'idempotency_key' => $idempotency,
            'description' => "تسوية عمولة تسويقية للتحويل رقم [{$conversion->id}]",
            'source_type' => Conversion::class,
            'source_id' => $conversion->id,
            'created_by' => $authorizer,
            'entries' => $entries,
            'metadata' => [
                'conversion_id' => $conversion->id,
                'campaign_id' => $campaign->id,
                'marketer_user_id' => $marketerUser->id,
                'brand_user_id' => $brandUser->id,
                'order_value' => (string) $conversion->order_value,
            ],
        ]);
    }

    /**
     * Compute system-wide trial balance per currency.
     * Verifies that total debits equal total credits across all entries.
     *
     * @return array<string, array{
     *     currency: string,
     *     total_debit: string,
     *     total_credit: string,
     *     difference: string,
     *     is_balanced: bool,
     *     entries_count: int,
     *     accounts: array
     * }>
     */
    public function getTrialBalance(?Currency $currencyFilter = null): array
    {
        $currencies = $currencyFilter ? [$currencyFilter->value] : Currency::values();
        $results = [];

        foreach ($currencies as $curr) {
            $entries = LedgerEntry::with('account')
                ->where('currency', $curr)
                ->get();

            $totalDebit = '0.0000';
            $totalCredit = '0.0000';
            $accountBreakdown = [];

            foreach ($entries as $e) {
                $accCode = $e->account->code;
                if (!isset($accountBreakdown[$accCode])) {
                    $accountBreakdown[$accCode] = [
                        'code' => $accCode,
                        'name_ar' => $e->account->name_ar,
                        'type' => $e->account->type->value,
                        'debit' => '0.0000',
                        'credit' => '0.0000',
                    ];
                }

                if ($e->entry_type === LedgerEntryType::DEBIT) {
                    $totalDebit = MoneyMath::add($totalDebit, $e->amount);
                    $accountBreakdown[$accCode]['debit'] = MoneyMath::add($accountBreakdown[$accCode]['debit'], $e->amount);
                } else {
                    $totalCredit = MoneyMath::add($totalCredit, $e->amount);
                    $accountBreakdown[$accCode]['credit'] = MoneyMath::add($accountBreakdown[$accCode]['credit'], $e->amount);
                }
            }

            $difference = MoneyMath::sub($totalDebit, $totalCredit);
            $isBalanced = MoneyMath::isZero($difference);

            $results[$curr] = [
                'currency' => $curr,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'difference' => $difference,
                'is_balanced' => $isBalanced,
                'entries_count' => $entries->count(),
                'accounts' => array_values($accountBreakdown),
            ];
        }

        return $results;
    }
}
