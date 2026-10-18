<?php

namespace App\Services\Ledger;

use App\Enums\AuditAction;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Models\CommissionSettlement;
use App\Models\Conversion;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AuditService;
use App\Services\Math\MoneyMath;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CommissionSettlementService
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected WalletService $walletService
    ) {}

    /**
     * Settle an approved conversion commission into the marketer's wallet from brand campaign escrow.
     * Enforces strict idempotency and double-entry accounting.
     */
    public function settleConversion(
        Conversion $conversion,
        User $authorizedBy,
        string $platformFeeAmount = '0.0000'
    ): CommissionSettlement {
        // 1. Validation of conversion state and relationships
        if ($conversion->status !== ConversionStatus::APPROVED) {
            throw new RuntimeException("لا يمكن تسوية عمولة تحويل حالته ليست معتمدة [{$conversion->status->value}].");
        }

        $campaign = $conversion->campaign;
        if (!$campaign || !$campaign->brandProfile || !$campaign->brandProfile->user) {
            throw new RuntimeException('بيانات الحملة أو العلامة التجارية المرتبطة بالتحويل غير مكتملة.');
        }

        $application = $conversion->campaignApplication;
        if (!$application || !$application->marketerProfile || !$application->marketerProfile->user) {
            throw new RuntimeException('بيانات المسوق أو طلب المشاركة المرتبط بالتحويل غير مكتملة.');
        }

        $currencyEnum = Currency::tryFrom($conversion->currency);
        if (!$currencyEnum) {
            throw new InvalidArgumentException("عملة التحويل غير مدعومة في النظام المالي: [{$conversion->currency}].");
        }

        $commissionAmount = MoneyMath::format($conversion->commission_value);
        if (!MoneyMath::isPositive($commissionAmount)) {
            throw new InvalidArgumentException("قيمة عمولة التحويل يجب أن تكون أكبر من صفر: [{$commissionAmount}].");
        }

        $feeAmount = MoneyMath::format($platformFeeAmount);

        // 2. Idempotency Check: strictly prevent duplicate settlement
        $existingSettlement = CommissionSettlement::where('conversion_id', $conversion->id)
            ->with(['ledgerTransaction', 'brandWallet', 'marketerWallet'])
            ->first();

        if ($existingSettlement) {
            return $existingSettlement;
        }

        $brandUser = $campaign->brandProfile->user;
        $marketerUser = $application->marketerProfile->user;

        return DB::transaction(function () use (
            $conversion,
            $campaign,
            $brandUser,
            $marketerUser,
            $currencyEnum,
            $commissionAmount,
            $feeAmount,
            $authorizedBy
        ) {
            // Lock wallets to ensure atomicity
            $brandWallet = $this->walletService->getOrCreateWallet($brandUser, $currencyEnum);
            $marketerWallet = $this->walletService->getOrCreateWallet($marketerUser, $currencyEnum);

            $brandWallet = Wallet::where('id', $brandWallet->id)->lockForUpdate()->first();
            $marketerWallet = Wallet::where('id', $marketerWallet->id)->lockForUpdate()->first();

            $totalEscrowDeduction = MoneyMath::add($commissionAmount, $feeAmount);

            // 3. Escrow Availability Check: Brand must have sufficient escrow funds reserved
            if (MoneyMath::comp($brandWallet->reserved_balance, $totalEscrowDeduction) < 0) {
                AuditService::log(
                    action: AuditAction::FINANCIAL_ADJUSTMENT,
                    userId: $authorizedBy->id,
                    entityType: Conversion::class,
                    entityId: $conversion->id,
                    payloadDiff: [
                        'conversion_id' => $conversion->id,
                        'required_escrow' => $totalEscrowDeduction,
                        'available_escrow' => (string) $brandWallet->reserved_balance,
                        'currency' => $currencyEnum->value,
                    ],
                    status: 'FAILED',
                    reason: "فشل تسوية عمولة التحويل #{$conversion->id}: رصيد ميزانية الحملة المحجوز بالضمان غير كافٍ."
                );

                throw new InsufficientFundsException(
                    "رصيد الضمان المحجوز في محفظة العلامة التجارية [{$brandWallet->reserved_balance} {$currencyEnum->value}] أقل من العمولة المستحقة [{$totalEscrowDeduction} {$currencyEnum->value}]."
                );
            }

            // 4. Double-Entry Posting
            $idempotencyKey = "conversion-settlement-{$conversion->id}";

            $entries = [
                // DEBIT: Brand Campaign Escrow Liability (Reserved balance decreases)
                [
                    'account_code' => '2020_CAMPAIGN_ESCROW_LIABILITY',
                    'wallet_id' => $brandWallet->id,
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => $totalEscrowDeduction,
                    'description' => "صرف عمولة مبيعات محققة للتحويل #{$conversion->external_order_id} من حملة [{$campaign->title}]",
                ],
                // CREDIT: Marketer Payable Liability (Available balance increases)
                [
                    'account_code' => '2030_MARKETER_PAYABLE_LIABILITY',
                    'wallet_id' => $marketerWallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => $commissionAmount,
                    'description' => "استحقاق عمولة مبيعات مؤكدة للتحويل #{$conversion->external_order_id} من حملة [{$campaign->title}]",
                ],
            ];

            // If platform fee is configured
            if (MoneyMath::isPositive($feeAmount)) {
                $entries[] = [
                    'account_code' => '4010_PLATFORM_COMMISSION_REVENUE',
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => $feeAmount,
                    'description' => "إيراد عمولة المنصة عن تحويل مبيعات #{$conversion->external_order_id}",
                ];
            }

            $tx = $this->ledgerService->postTransaction([
                'currency' => $currencyEnum,
                'transaction_type' => LedgerTransactionType::COMMISSION_EARNING,
                'idempotency_key' => $idempotencyKey,
                'description' => "تسوية عمولة تسويقية للتحويل رقم [{$conversion->id}] (طلب #{$conversion->external_order_id})",
                'source_type' => Conversion::class,
                'source_id' => $conversion->id,
                'created_by' => $authorizedBy,
                'entries' => $entries,
                'metadata' => [
                    'conversion_id' => $conversion->id,
                    'external_order_id' => $conversion->external_order_id,
                    'campaign_id' => $campaign->id,
                    'marketer_user_id' => $marketerUser->id,
                    'brand_user_id' => $brandUser->id,
                    'order_value' => (string) $conversion->order_value,
                    'commission_value' => $commissionAmount,
                ],
            ]);

            // 5. Create Commission Settlement Record
            $settlement = CommissionSettlement::create([
                'conversion_id' => $conversion->id,
                'ledger_transaction_id' => $tx->id,
                'brand_wallet_id' => $brandWallet->id,
                'marketer_wallet_id' => $marketerWallet->id,
                'currency' => $currencyEnum->value,
                'commission_amount' => $commissionAmount,
                'platform_fee_amount' => $feeAmount,
                'settled_by' => $authorizedBy->id,
                'settled_at' => now(),
            ]);

            AuditService::log(
                action: AuditAction::COMMISSION_SETTLED,
                userId: $authorizedBy->id,
                entityType: Conversion::class,
                entityId: $conversion->id,
                payloadDiff: [
                    'new' => [
                        'conversion_id' => $conversion->id,
                        'settlement_id' => $settlement->id,
                        'transaction_id' => $tx->id,
                        'commission_amount' => $commissionAmount,
                        'currency' => $currencyEnum->value,
                        'marketer_id' => $marketerUser->id,
                    ],
                ],
                status: 'SUCCESS',
                reason: "تسوية وصرف عمولة تسويقية مستحقة للتحويل #{$conversion->external_order_id}"
            );

            return $settlement->load(['ledgerTransaction', 'brandWallet', 'marketerWallet']);
        });
    }

    /**
     * Reverse a commission settlement when a conversion is cancelled.
     * Enforces that reversal can only happen once, and checks marketer balance.
     */
    public function reverseSettlement(
        Conversion $conversion,
        User $performedBy,
        string $reason
    ): ?CommissionSettlement {
        $settlement = CommissionSettlement::where('conversion_id', $conversion->id)
            ->with(['ledgerTransaction', 'brandWallet', 'marketerWallet'])
            ->first();

        if (!$settlement) {
            // Conversion was never settled financially; no ledger reversal needed
            return null;
        }

        if ($settlement->isReversed()) {
            throw new RuntimeException("تم عكس تسوية هذا التحويل مسبقاً بقيد تصحيحي.");
        }

        return DB::transaction(function () use ($settlement, $conversion, $performedBy, $reason) {
            $marketerWallet = Wallet::where('id', $settlement->marketer_wallet_id)->lockForUpdate()->first();
            $brandWallet = Wallet::where('id', $settlement->brand_wallet_id)->lockForUpdate()->first();

            // Check if marketer has sufficient available balance to claw back
            if (MoneyMath::comp($marketerWallet->available_balance, $settlement->commission_amount) < 0) {
                AuditService::log(
                    action: AuditAction::FINANCIAL_ADJUSTMENT,
                    userId: $performedBy->id,
                    entityType: Conversion::class,
                    entityId: $conversion->id,
                    payloadDiff: [
                        'conversion_id' => $conversion->id,
                        'commission_amount' => (string) $settlement->commission_amount,
                        'marketer_available' => (string) $marketerWallet->available_balance,
                    ],
                    status: 'PENDING_REVIEW',
                    reason: "تعذر الاسترداد التلقائي لعمولة التحويل الملغي: رصيد المسوق المتاح أقل من العمولة المصروفة مسبقاً."
                );

                throw new InsufficientFundsException(
                    "رصيد المسوق المتاح [{$marketerWallet->available_balance} {$settlement->currency->value}] أقل من قيمة العمولة المراد عكسها [{$settlement->commission_amount} {$settlement->currency->value}]. يتطلب تدخلاً إدارياً."
                );
            }

            // Perform double-entry reversal
            $reversalTx = $this->ledgerService->reverseTransaction(
                $settlement->ledgerTransaction,
                "إلغاء التحويل #{$conversion->external_order_id}: {$reason}",
                $performedBy
            );

            $settlement->update([
                'reversal_ledger_transaction_id' => $reversalTx->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            AuditService::log(
                action: AuditAction::COMMISSION_SETTLEMENT_REVERSED,
                userId: $performedBy->id,
                entityType: Conversion::class,
                entityId: $conversion->id,
                payloadDiff: [
                    'old' => ['status' => 'SETTLED'],
                    'new' => [
                        'status' => 'REVERSED',
                        'reversal_transaction_id' => $reversalTx->id,
                        'reason' => $reason,
                    ],
                ],
                status: 'SUCCESS',
                reason: $reason
            );

            return $settlement->fresh(['reversalLedgerTransaction']);
        });
    }
}
