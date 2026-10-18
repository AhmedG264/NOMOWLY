<?php

namespace App\Services\Ledger;

use App\Enums\AuditAction;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionType;
use App\Enums\PayoutRequestStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AuditService;
use App\Services\Math\MoneyMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PayoutService
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected WalletService $walletService
    ) {}

    /**
     * Create a new payout request from the marketer's available wallet balance.
     * Atomically holds the requested amount in reserved_balance to prevent double-spending.
     */
    public function createPayoutRequest(
        User $marketer,
        Wallet $wallet,
        string|int $amount,
        array $payoutData = []
    ): PayoutRequest {
        if ($wallet->user_id !== $marketer->id) {
            throw new InvalidArgumentException('المحفظة المحددة لا تنتمي إلى هذا المستخدم.');
        }

        if (!$wallet->isActive()) {
            throw new RuntimeException('لا يمكن طلب سحب أرباح من محفظة مجمدة أو مغلقة.');
        }

        $normAmount = MoneyMath::format($amount);
        if (!MoneyMath::isPositive($normAmount)) {
            throw new InvalidArgumentException("مبلغ السحب يجب أن يكون موجباً وأكبر من صفر: [{$normAmount}].");
        }

        $idempotencyKey = $payoutData['idempotency_key'] ?? ('payout-req-' . $marketer->id . '-' . Str::random(12));

        // Check if an existing request with this idempotency key exists
        $existing = PayoutRequest::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($marketer, $wallet, $normAmount, $idempotencyKey, $payoutData) {
            // Lock wallet row for update
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $currentAvailable = MoneyMath::format($lockedWallet->available_balance);
            $currentReserved = MoneyMath::format($lockedWallet->reserved_balance);

            if (MoneyMath::comp($currentAvailable, $normAmount) < 0) {
                throw new InsufficientFundsException(
                    "الرصيد المتاح للسحب في المحفظة [{$currentAvailable} {$lockedWallet->currency->value}] أقل من المبلغ المطلوب [{$normAmount} {$lockedWallet->currency->value}]."
                );
            }

            // Reserve requested amount
            $newAvailable = MoneyMath::sub($currentAvailable, $normAmount);
            $newReserved = MoneyMath::add($currentReserved, $normAmount);

            $lockedWallet->update([
                'available_balance' => $newAvailable,
                'reserved_balance' => $newReserved,
            ]);

            // Mask account reference for privacy (e.g. SA44 1234 5678 -> SA** **** 5678)
            $rawRef = trim($payoutData['account_reference'] ?? '');
            $maskedRef = null;
            if (!empty($rawRef)) {
                $len = strlen($rawRef);
                if ($len > 8) {
                    $maskedRef = substr($rawRef, 0, 4) . str_repeat('*', max(4, $len - 8)) . substr($rawRef, -4);
                } else {
                    $maskedRef = str_repeat('*', $len);
                }
            }

            $requestNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $payoutRequest = PayoutRequest::create([
                'request_number' => $requestNumber,
                'user_id' => $marketer->id,
                'wallet_id' => $lockedWallet->id,
                'currency' => $lockedWallet->currency->value,
                'amount' => $normAmount,
                'status' => PayoutRequestStatus::REQUESTED,
                'bank_name' => $payoutData['bank_name'] ?? null,
                'account_holder_name' => $payoutData['account_holder_name'] ?? null,
                'account_reference' => $maskedRef,
                'admin_notes' => null,
                'rejection_reason' => null,
                'idempotency_key' => $idempotencyKey,
            ]);

            AuditService::log(
                action: AuditAction::PAYOUT_REQUESTED,
                userId: $marketer->id,
                entityType: PayoutRequest::class,
                entityId: $payoutRequest->id,
                payloadDiff: [
                    'new' => [
                        'request_number' => $requestNumber,
                        'amount' => $normAmount,
                        'currency' => $lockedWallet->currency->value,
                        'bank_name' => $payoutData['bank_name'] ?? null,
                    ],
                ],
                status: 'SUCCESS',
                reason: "تقديم طلب سحب أرباح وحجز المبلغ من الرصيد المتاح"
            );

            return $payoutRequest;
        });
    }

    /**
     * Approve a payout request (Ready for financial disbursement).
     * Enforces that the requester CANNOT approve their own payout request.
     */
    public function approvePayoutRequest(PayoutRequest $request, User $admin): PayoutRequest
    {
        if ($admin->id === $request->user_id) {
            throw new RuntimeException('غير مصرح لك باعتماد طلب سحب أرباح خاص بحسابك الشخصي.');
        }

        if ($request->status !== PayoutRequestStatus::REQUESTED) {
            throw new RuntimeException("لا يمكن اعتماد طلب سحب حالته [{$request->status->value}].");
        }

        $request->update([
            'status' => PayoutRequestStatus::APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        AuditService::log(
            action: AuditAction::PAYOUT_APPROVED,
            userId: $admin->id,
            entityType: PayoutRequest::class,
            entityId: $request->id,
            payloadDiff: [
                'old' => ['status' => PayoutRequestStatus::REQUESTED->value],
                'new' => [
                    'status' => PayoutRequestStatus::APPROVED->value,
                    'reviewed_by' => $admin->id,
                ],
            ],
            status: 'SUCCESS',
            reason: "اعتماد طلب سحب الأرباح وبانتظار التنفيذ المصرفي"
        );

        return $request->fresh();
    }

    /**
     * Reject a payout request and restore the held funds back to the marketer's available balance.
     */
    public function rejectPayoutRequest(PayoutRequest $request, User $admin, string $reason): PayoutRequest
    {
        if (!in_array($request->status, [PayoutRequestStatus::REQUESTED, PayoutRequestStatus::APPROVED], true)) {
            throw new RuntimeException("لا يمكن رفض طلب سحب بحالة [{$request->status->value}].");
        }

        return DB::transaction(function () use ($request, $admin, $reason) {
            $wallet = Wallet::where('id', $request->wallet_id)->lockForUpdate()->first();

            $currentAvailable = MoneyMath::format($wallet->available_balance);
            $currentReserved = MoneyMath::format($wallet->reserved_balance);
            $amount = MoneyMath::format($request->amount);

            // Restore held funds
            $newAvailable = MoneyMath::add($currentAvailable, $amount);
            $newReserved = MoneyMath::sub($currentReserved, $amount);

            $wallet->update([
                'available_balance' => $newAvailable,
                'reserved_balance' => $newReserved,
            ]);

            $oldStatus = $request->status->value;

            $request->update([
                'status' => PayoutRequestStatus::REJECTED,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            AuditService::log(
                action: AuditAction::PAYOUT_REJECTED,
                userId: $admin->id,
                entityType: PayoutRequest::class,
                entityId: $request->id,
                payloadDiff: [
                    'old' => ['status' => $oldStatus],
                    'new' => [
                        'status' => PayoutRequestStatus::REJECTED->value,
                        'rejection_reason' => $reason,
                        'refunded_amount' => $amount,
                    ],
                ],
                status: 'REJECTED',
                reason: $reason
            );

            return $request->fresh();
        });
    }

    /**
     * Mark payout as paid (Confirm external bank transfer and post Double-Entry Ledger transaction).
     */
    public function markAsPaid(
        PayoutRequest $request,
        User $admin,
        string $paymentReference,
        ?string $notes = null
    ): PayoutRequest {
        if ($admin->id === $request->user_id) {
            throw new RuntimeException('غير مصرح لك بتسجيل صرف أرباح لحسابك الشخصي.');
        }

        if (!in_array($request->status, [PayoutRequestStatus::APPROVED, PayoutRequestStatus::PROCESSING], true)) {
            throw new RuntimeException("لا يمكن تأكيد دفع طلب سحب بحالة [{$request->status->value}]. يجب اعتماده أولاً.");
        }

        $cleanPaymentRef = trim($paymentReference);
        if (empty($cleanPaymentRef)) {
            throw new InvalidArgumentException('الرقم المرجعي للتحويل البنكي إلزامي لتأكيد عملية الصرف.');
        }

        return DB::transaction(function () use ($request, $admin, $cleanPaymentRef, $notes) {
            $wallet = Wallet::where('id', $request->wallet_id)->lockForUpdate()->first();

            $currentReserved = MoneyMath::format($wallet->reserved_balance);
            $amount = MoneyMath::format($request->amount);

            if (MoneyMath::comp($currentReserved, $amount) < 0) {
                throw new InsufficientFundsException("المبلغ المحجوز في المحفظة أقل من المبلغ المراد صرفه.");
            }

            // Post Double-Entry Transaction:
            // DEBIT: 2030_MARKETER_PAYABLE_LIABILITY (marketer wallet liability decreases, reserved balance released)
            // CREDIT: 1010_PLATFORM_CASH (platform cash decreases by payout sent)
            $payoutTx = $this->ledgerService->postTransaction([
                'currency' => $request->currency,
                'transaction_type' => LedgerTransactionType::WITHDRAWAL,
                'idempotency_key' => "payout-paid-{$request->id}",
                'description' => "صرف أرباح للمسوق بموجب طلب رقم [{$request->request_number}] مرجع تحويل: {$cleanPaymentRef}",
                'source_type' => PayoutRequest::class,
                'source_id' => $request->id,
                'created_by' => $admin,
                'entries' => [
                    [
                        'account_code' => '2030_MARKETER_PAYABLE_LIABILITY',
                        'wallet_id' => $wallet->id,
                        'entry_type' => LedgerEntryType::DEBIT,
                        'amount' => $amount,
                        'description' => "خصم التزام الأرباح المنفذة بموجب طلب سحب #{$request->request_number}",
                    ],
                    [
                        'account_code' => '1010_PLATFORM_CASH',
                        'entry_type' => LedgerEntryType::CREDIT,
                        'amount' => $amount,
                        'description' => "خروج نقدية من حساب المنصة البنكي لصرف أرباح مسوق (مرجع: {$cleanPaymentRef})",
                    ],
                ],
                'metadata' => [
                    'payout_request_id' => $request->id,
                    'request_number' => $request->request_number,
                    'payment_reference' => $cleanPaymentRef,
                    'notes' => $notes,
                ],
            ]);

            $request->update([
                'status' => PayoutRequestStatus::PAID,
                'payment_reference' => $cleanPaymentRef,
                'admin_notes' => $notes,
                'payout_ledger_transaction_id' => $payoutTx->id,
                'paid_by' => $admin->id,
                'paid_at' => now(),
            ]);

            AuditService::log(
                action: AuditAction::PAYOUT_PAID,
                userId: $admin->id,
                entityType: PayoutRequest::class,
                entityId: $request->id,
                payloadDiff: [
                    'old' => ['status' => PayoutRequestStatus::APPROVED->value],
                    'new' => [
                        'status' => PayoutRequestStatus::PAID->value,
                        'payment_reference' => $cleanPaymentRef,
                        'transaction_id' => $payoutTx->id,
                        'paid_amount' => $amount,
                    ],
                ],
                status: 'SUCCESS',
                reason: "تأكيد صرف أرباح المسوق مصرفياً وترحيل القيد في دفتر الأستاذ"
            );

            return $request->fresh(['payoutTransaction']);
        });
    }
}
