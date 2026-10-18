<?php

namespace App\Services\Payment;

use App\Enums\AuditAction;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionType;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\AuditLog;
use App\Models\PaymentDeposit;
use App\Models\PaymentRefund;
use App\Models\PaymentWebhook;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AuditService;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTO\PaymentGatewayResponse;
use App\Services\Payment\DTO\PaymentGatewayResult;
use App\Services\Payment\DTO\WebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PaymentDepositService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected LedgerService $ledgerService,
        protected WalletService $walletService
    ) {}

    /**
     * Initiate a new deposit checkout session.
     *
     * @param array{
     *     user: User,
     *     currency: Currency|string,
     *     amount: string|float|int,
     *     provider?: ?string,
     *     payment_method?: ?string,
     *     options?: array
     * } $data
     */
    public function initiateDeposit(array $data): array
    {
        $user = $data['user'];
        $currency = $data['currency'] instanceof Currency ? $data['currency'] : Currency::from(strtoupper(trim($data['currency'])));
        $amount = MoneyMath::format($data['amount']);

        if (!MoneyMath::isPositive($amount)) {
            throw new InvalidArgumentException("مبلغ الإيداع يجب أن يكون أكبر من الصفر [{$amount} {$currency->value}].");
        }

        // Get or create the user's brand wallet for this currency
        $wallet = $this->walletService->getOrCreateWallet($user, $currency);

        // Resolve requested or default payment gateway
        $providerName = $data['provider'] ?? null;
        if ($providerName) {
            $gateway = $this->gatewayManager->getGateway($providerName);
        } else {
            $gateway = $this->gatewayManager->getDefaultGatewayFor($currency);
            if (!$gateway) {
                throw new RuntimeException("لا توجد بوابة دفع مفعلة تدعم عملة [{$currency->value}] حالياً.");
            }
        }

        if (!$gateway->supportsCurrency($currency)) {
            throw new InvalidArgumentException("مزود الدفع [{$gateway->getProvider()->label()}] لا يدعم العملة [{$currency->value}].");
        }

        $depositNumber = 'DEP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        $idempotencyKey = $data['idempotency_key'] ?? ('DEP_' . $user->id . '_' . $wallet->id . '_' . microtime(true) . '_' . Str::random(6));

        // In standard configuration, net amount credited equals the amount
        $feeAmount = '0.0000';
        $netAmount = $amount;

        $deposit = DB::transaction(function () use (
            $user,
            $wallet,
            $currency,
            $amount,
            $feeAmount,
            $netAmount,
            $gateway,
            $data,
            $depositNumber,
            $idempotencyKey
        ) {
            $deposit = PaymentDeposit::create([
                'deposit_number' => $depositNumber,
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'currency' => $currency,
                'amount' => $amount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'provider' => $gateway->getProvider(),
                'payment_method' => $data['payment_method'] ?? 'card',
                'status' => PaymentDepositStatus::PENDING,
                'idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'ip' => request()->ip(),
                    'user_agent' => substr((string) request()->userAgent(), 0, 255),
                    'options' => $data['options'] ?? [],
                ],
            ]);

            AuditService::log(
                action: AuditAction::PAYMENT_DEPOSIT_INITIATED,
                userId: $user->id,
                entityType: PaymentDeposit::class,
                entityId: $deposit->id,
                payloadDiff: [
                    'new' => [
                        'deposit_number' => $deposit->deposit_number,
                        'amount' => $deposit->amount,
                        'currency' => $deposit->currency->value,
                        'provider' => $deposit->provider->value,
                    ],
                ],
                status: 'SUCCESS',
                reason: "بدء جلسة شحن رصيد المحفظة بمبلغ {$amount} {$currency->value}"
            );

            return $deposit;
        });

        // Initialize session with the gateway
        $gatewayResponse = $gateway->createPayment($deposit, $data['options'] ?? []);

        if ($gatewayResponse->transactionId()) {
            $deposit->update([
                'provider_transaction_id' => $gatewayResponse->transactionId(),
            ]);
        }

        return [
            'deposit' => $deposit,
            'gateway_response' => $gatewayResponse,
        ];
    }

    /**
     * Confirm deposit with reliable double-entry ledger posting.
     * Strictly idempotent: executing multiple times is 100% safe.
     */
    public function confirmDeposit(
        PaymentDeposit $deposit,
        ?string $providerTransactionId = null,
        ?string $confirmedFee = null,
        array $extraMetadata = []
    ): PaymentDeposit {
        return DB::transaction(function () use ($deposit, $providerTransactionId, $confirmedFee, $extraMetadata) {
            // Lock deposit for update to prevent concurrent confirmation races
            /** @var PaymentDeposit $lockedDeposit */
            $lockedDeposit = PaymentDeposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            // Idempotency check: If already succeeded, refunded, or partially refunded, return immediately
            if (in_array($lockedDeposit->status, [
                PaymentDepositStatus::SUCCEEDED,
                PaymentDepositStatus::REFUNDED,
                PaymentDepositStatus::PARTIALLY_REFUNDED,
            ], true)) {
                return $lockedDeposit;
            }

            // Lock the target wallet for update
            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::where('id', $lockedDeposit->wallet_id)->lockForUpdate()->firstOrFail();

            // Update fee and net amount if gateway reported actual transaction processing fees
            $actualFee = $confirmedFee !== null ? MoneyMath::format($confirmedFee) : MoneyMath::format($lockedDeposit->fee_amount);
            $amount = MoneyMath::format($lockedDeposit->amount);

            // Compute net amount credited to brand wallet (Nomowly policy: platform absorbs payment fees)
            $netCreditedToWallet = $amount;
            $netAmount = $netCreditedToWallet;

            // Update provider transaction id
            $txId = $providerTransactionId ?: $lockedDeposit->provider_transaction_id ?: ('tx_' . Str::random(16));

            // Post balanced double-entry transaction into the financial ledger:
            // If gateway fee > 0:
            //   Dr. 1010_PLATFORM_CASH: Net cash reaching platform clearing account ($amount - $actualFee)
            //   Dr. 5030_PAYMENT_GATEWAY_FEE_EXPENSE: Payment gateway processing fee expense ($actualFee)
            //   Cr. 2010_BRAND_WALLET_LIABILITY: Full brand wallet liability credit ($amount)
            // If gateway fee == 0:
            //   Dr. 1010_PLATFORM_CASH: Full deposit amount ($amount)
            //   Cr. 2010_BRAND_WALLET_LIABILITY: Full brand wallet liability credit ($amount)
            if (MoneyMath::isPositive($actualFee)) {
                $netCashSettlement = MoneyMath::sub($amount, $actualFee);
                $ledgerEntries = [
                    [
                        'account_code' => '1010_PLATFORM_CASH',
                        'entry_type' => LedgerEntryType::DEBIT,
                        'amount' => $netCashSettlement,
                        'description' => "صافي نقدية محصلة ومودعة في الحساب الوسيط بعد استقطاع رسوم البوابة عبر [{$lockedDeposit->provider->label()}] عملية رقم [{$lockedDeposit->deposit_number}]",
                    ],
                    [
                        'account_code' => '5030_PAYMENT_GATEWAY_FEE_EXPENSE',
                        'entry_type' => LedgerEntryType::DEBIT,
                        'amount' => $actualFee,
                        'description' => "مصروف رسوم بوابة الدفع الإلكتروني [{$lockedDeposit->provider->label()}] لعملية الإيداع [{$lockedDeposit->deposit_number}]",
                    ],
                    [
                        'account_code' => '2010_BRAND_WALLET_LIABILITY',
                        'wallet_id' => $lockedWallet->id,
                        'entry_type' => LedgerEntryType::CREDIT,
                        'amount' => $netCreditedToWallet,
                        'description' => "شحن رصيد متاح في محفظة المتجر بموجب عملية الإيداع [{$lockedDeposit->deposit_number}]",
                    ],
                ];
            } else {
                $ledgerEntries = [
                    [
                        'account_code' => '1010_PLATFORM_CASH',
                        'entry_type' => LedgerEntryType::DEBIT,
                        'amount' => $netCreditedToWallet,
                        'description' => "نقدية محصلة ومودعة في الحساب الوسيط عبر [{$lockedDeposit->provider->label()}] عملية رقم [{$lockedDeposit->deposit_number}]",
                    ],
                    [
                        'account_code' => '2010_BRAND_WALLET_LIABILITY',
                        'wallet_id' => $lockedWallet->id,
                        'entry_type' => LedgerEntryType::CREDIT,
                        'amount' => $netCreditedToWallet,
                        'description' => "شحن رصيد متاح في محفظة المتجر بموجب عملية الإيداع [{$lockedDeposit->deposit_number}]",
                    ],
                ];
            }

            $ledgerTx = $this->ledgerService->postTransaction([
                'currency' => $lockedDeposit->currency,
                'transaction_type' => LedgerTransactionType::DEPOSIT,
                'idempotency_key' => "DEPOSIT_{$lockedDeposit->deposit_number}",
                'description' => "إيداع بنكي وشحن رصيد محفظة المتجر [{$lockedDeposit->deposit_number}]",
                'source_type' => PaymentDeposit::class,
                'source_id' => $lockedDeposit->id,
                'entries' => $ledgerEntries,
                'metadata' => [
                    'deposit_number' => $lockedDeposit->deposit_number,
                    'provider' => $lockedDeposit->provider->value,
                    'provider_transaction_id' => $txId,
                    'fee_amount' => $actualFee,
                    'extra' => $extraMetadata,
                ],
                'created_by' => $lockedDeposit->user,
            ]);

            // Mark deposit as succeeded
            $lockedDeposit->update([
                'status' => PaymentDepositStatus::SUCCEEDED,
                'provider_transaction_id' => $txId,
                'fee_amount' => $actualFee,
                'ledger_transaction_id' => $ledgerTx->id,
                'paid_at' => now(),
                'metadata' => array_merge($lockedDeposit->metadata ?? [], [
                    'confirmed_at' => now()->toIso8601String(),
                    'ledger_transaction_number' => $ledgerTx->transaction_number,
                ]),
            ]);

            // Audit log
            AuditService::log(
                action: AuditAction::PAYMENT_DEPOSIT_SUCCEEDED,
                userId: $lockedDeposit->user_id,
                entityType: PaymentDeposit::class,
                entityId: $lockedDeposit->id,
                payloadDiff: [
                    'old' => ['status' => PaymentDepositStatus::PENDING->value],
                    'new' => [
                        'status' => PaymentDepositStatus::SUCCEEDED->value,
                        'amount' => $lockedDeposit->amount,
                        'net_amount' => $lockedDeposit->net_amount,
                        'currency' => $lockedDeposit->currency->value,
                        'ledger_tx_id' => $ledgerTx->id,
                    ],
                ],
                status: 'SUCCESS',
                reason: "تأكيد إيداع وشحن محفظة المتجر بمبلغ {$netAmount} {$lockedDeposit->currency->value}"
            );

            return $lockedDeposit;
        });
    }

    /**
     * Mark deposit as failed with clear audit trail.
     */
    public function failDeposit(PaymentDeposit $deposit, string $reason, array $raw = []): PaymentDeposit
    {
        return DB::transaction(function () use ($deposit, $reason, $raw) {
            /** @var PaymentDeposit $lockedDeposit */
            $lockedDeposit = PaymentDeposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            if ($lockedDeposit->status === PaymentDepositStatus::SUCCEEDED) {
                throw new RuntimeException('لا يمكن تغيير حالة إيداع مؤكد ومرحل محاسبياً إلى فاشل.');
            }

            $lockedDeposit->update([
                'status' => PaymentDepositStatus::FAILED,
                'failure_reason' => $reason,
                'metadata' => array_merge($lockedDeposit->metadata ?? [], [
                    'failed_at' => now()->toIso8601String(),
                    'raw_error' => $raw,
                ]),
            ]);

            AuditService::log(
                action: AuditAction::PAYMENT_DEPOSIT_FAILED,
                userId: $lockedDeposit->user_id,
                entityType: PaymentDeposit::class,
                entityId: $lockedDeposit->id,
                payloadDiff: [
                    'new' => [
                        'status' => PaymentDepositStatus::FAILED->value,
                        'reason' => $reason,
                    ],
                ],
                status: 'FAILED',
                reason: "فشل عملية الدفع: {$reason}"
            );

            return $lockedDeposit;
        });
    }

    /**
     * Process an incoming payment webhook with strict signature verification & deduplication.
     */
    public function processWebhook(Request $request, PaymentProvider|string $provider): array
    {
        $gateway = $this->gatewayManager->getGateway($provider);

        // 1. Verify Webhook Signature / Token
        if (!$gateway->verifyWebhookSignature($request)) {
            Log::warning('Payment Webhook signature verification failed', [
                'provider' => $gateway->getProvider()->value,
                'ip' => $request->ip(),
            ]);

            throw new UnauthorizedHttpException('Payment Gateway Webhook', 'فشل التحقق من التوقيع الأمني للـ Webhook.');
        }

        // 2. Parse Webhook payload
        $payload = $gateway->parseWebhook($request);

        // 3. Deduplication: check if this event has already been recorded
        $existingWebhook = PaymentWebhook::where('provider', $gateway->getProvider())
            ->where('event_id', $payload->eventId())
            ->first();

        if ($existingWebhook && $existingWebhook->status === 'PROCESSED') {
            Log::info('Payment Webhook already processed (idempotent)', [
                'provider' => $gateway->getProvider()->value,
                'event_id' => $payload->eventId(),
            ]);

            return [
                'success' => true,
                'message' => 'Event already processed',
                'idempotent' => true,
            ];
        }

        // 4. Locate deposit record
        $deposit = null;
        if ($payload->depositNumber()) {
            $deposit = PaymentDeposit::where('deposit_number', $payload->depositNumber())->first();
        }

        if (!$deposit && $payload->transactionId()) {
            $deposit = PaymentDeposit::where('provider_transaction_id', $payload->transactionId())->first();
        }

        if (!$deposit) {
            Log::warning('Payment Webhook received for non-existent deposit', [
                'provider' => $gateway->getProvider()->value,
                'deposit_number' => $payload->depositNumber(),
                'tx_id' => $payload->transactionId(),
            ]);

            // Save webhook audit record for forensics
            PaymentWebhook::create([
                'provider' => $gateway->getProvider(),
                'event_id' => $payload->eventId(),
                'event_type' => $payload->eventType(),
                'payload' => $this->sanitizePayload($request->all()),
                'signature' => $request->header('X-Signature') ?? $request->header('hash'),
                'status' => 'FAILED',
                'error_message' => 'لم يتم العثور على سجل الإيداع المرتبط في المنصة.',
            ]);

            return [
                'success' => false,
                'message' => 'Deposit not found',
            ];
        }

        // 5. Tamper-proofing: Verify currency and amount
        if ($payload->currency() && strtoupper($payload->currency()) !== $deposit->currency->value) {
            $errorMsg = "تعارض في العملة: المزود أرسل [{$payload->currency()}] بينما العملية مسجلة بـ [{$deposit->currency->value}]";
            Log::alert('Payment Webhook Currency Mismatch Security Warning', [
                'deposit' => $deposit->deposit_number,
                'webhook_currency' => $payload->currency(),
                'deposit_currency' => $deposit->currency->value,
            ]);

            PaymentWebhook::create([
                'provider' => $gateway->getProvider(),
                'event_id' => $payload->eventId(),
                'event_type' => $payload->eventType(),
                'payload' => $this->sanitizePayload($request->all()),
                'deposit_id' => $deposit->id,
                'status' => 'FAILED',
                'error_message' => $errorMsg,
            ]);

            throw new HttpException(400, $errorMsg);
        }

        if ($payload->amount() && MoneyMath::comp($payload->amount(), $deposit->amount) !== 0) {
            $errorMsg = "تعارض في مبلغ العملية: المزود أرسل [{$payload->amount()}] بينما العملية مسجلة بـ [{$deposit->amount}]";
            Log::alert('Payment Webhook Amount Mismatch Security Warning', [
                'deposit' => $deposit->deposit_number,
                'webhook_amount' => $payload->amount(),
                'deposit_amount' => $deposit->amount,
            ]);

            PaymentWebhook::create([
                'provider' => $gateway->getProvider(),
                'event_id' => $payload->eventId(),
                'event_type' => $payload->eventType(),
                'payload' => $this->sanitizePayload($request->all()),
                'deposit_id' => $deposit->id,
                'status' => 'FAILED',
                'error_message' => $errorMsg,
            ]);

            throw new HttpException(400, $errorMsg);
        }

        // 6. Process according to status
        if ($payload->status() === PaymentDepositStatus::SUCCEEDED) {
            $this->confirmDeposit(
                deposit: $deposit,
                providerTransactionId: $payload->transactionId(),
                confirmedFee: $payload->fee(),
                extraMetadata: ['webhook_event_id' => $payload->eventId()]
            );
        } elseif ($payload->status() === PaymentDepositStatus::FAILED) {
            $this->failDeposit(
                deposit: $deposit,
                reason: $payload->failureReason() ?? 'فشلت عملية الدفع لدى المزود الخارجي.',
                raw: $this->sanitizePayload($request->all())
            );
        } elseif (in_array($payload->status(), [PaymentDepositStatus::REFUNDED, PaymentDepositStatus::PARTIALLY_REFUNDED], true)) {
            Log::info('Payment Webhook acknowledged refund notification', [
                'deposit' => $deposit->deposit_number,
                'event_id' => $payload->eventId(),
                'status' => $payload->status()->value,
            ]);
        }

        // 7. Save webhook record
        PaymentWebhook::updateOrCreate(
            [
                'provider' => $gateway->getProvider(),
                'event_id' => $payload->eventId(),
            ],
            [
                'event_type' => $payload->eventType(),
                'payload' => $this->sanitizePayload($request->all()),
                'signature' => $request->header('X-Signature') ?? $request->header('hash'),
                'status' => 'PROCESSED',
                'deposit_id' => $deposit->id,
                'processed_at' => now(),
            ]
        );

        AuditService::log(
            action: AuditAction::PAYMENT_WEBHOOK_PROCESSED,
            userId: $deposit->user_id,
            entityType: PaymentDeposit::class,
            entityId: $deposit->id,
            payloadDiff: [
                'event_id' => $payload->eventId(),
                'event_type' => $payload->eventType(),
                'status' => $payload->status()->value,
            ],
            status: 'SUCCESS',
            reason: "معالجة إشعار Webhook ناجح للعملية {$deposit->deposit_number}"
        );

        return [
            'success' => true,
            'deposit_number' => $deposit->deposit_number,
            'status' => $deposit->status->value,
        ];
    }

    /**
     * Process full or partial refund with ledger reverse entries and balance protection.
     *
     * @param array{
     *     deposit: PaymentDeposit,
     *     amount: string|float|int,
     *     reason: string,
     *     performer?: ?User
     * } $data
     */
    public function processRefund(array $data): PaymentRefund
    {
        $deposit = $data['deposit'];
        $amount = MoneyMath::format($data['amount']);
        $reason = trim((string) $data['reason']);
        $performer = $data['performer'] ?? auth()->user();

        if (!MoneyMath::isPositive($amount)) {
            throw new InvalidArgumentException("مبلغ الاسترداد يجب أن يكون أكبر من الصفر [{$amount} {$deposit->currency->value}].");
        }

        if (empty($reason)) {
            throw new InvalidArgumentException('سبب الاسترداد إلزامي لتوثيق القيود المحاسبية.');
        }

        return DB::transaction(function () use ($deposit, $amount, $reason, $performer) {
            /** @var PaymentDeposit $lockedDeposit */
            $lockedDeposit = PaymentDeposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            if (!in_array($lockedDeposit->status, [PaymentDepositStatus::SUCCEEDED, PaymentDepositStatus::PARTIALLY_REFUNDED], true)) {
                throw new RuntimeException("لا يمكن استرداد عملية في حالة [{$lockedDeposit->status->label()}]. يجب أن تكون العملية مؤكدة وناجحة.");
            }

            // 1. Verify remaining refundable amount
            $maxRefundable = $lockedDeposit->remainingRefundableAmount();
            if (MoneyMath::comp($amount, $maxRefundable) > 0) {
                throw new InvalidArgumentException(
                    "المبلغ المطلوب استرداده [{$amount} {$lockedDeposit->currency->value}] يتجاوز الرصيد القابل للاسترداد [{$maxRefundable} {$lockedDeposit->currency->value}]."
                );
            }

            // 2. Verify brand wallet has sufficient available balance
            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::where('id', $lockedDeposit->wallet_id)->lockForUpdate()->firstOrFail();
            $available = MoneyMath::format($lockedWallet->available_balance);

            if (MoneyMath::comp($available, $amount) < 0) {
                throw new InsufficientFundsException(
                    "الرصيد المتاح في محفظة المتجر [{$available} {$lockedWallet->currency->value}] أقل من مبلغ الاسترداد المطلوب [{$amount} {$lockedWallet->currency->value}]. لا يمكن استرداد مبالغ تم حجزها للحملات أو إنفاقها."
                );
            }

            $refundNumber = 'REF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
            $idempotencyKey = 'REF_' . $lockedDeposit->id . '_' . $refundNumber;

            // 3. Create Refund record
            $refund = PaymentRefund::create([
                'refund_number' => $refundNumber,
                'deposit_id' => $lockedDeposit->id,
                'user_id' => $lockedDeposit->user_id,
                'amount' => $amount,
                'currency' => $lockedDeposit->currency,
                'reason' => $reason,
                'status' => PaymentRefundStatus::REQUESTED,
                'idempotency_key' => $idempotencyKey,
                'created_by' => $performer?->id,
            ]);

            AuditService::log(
                action: AuditAction::PAYMENT_REFUND_REQUESTED,
                userId: $performer?->id ?? $lockedDeposit->user_id,
                entityType: PaymentRefund::class,
                entityId: $refund->id,
                payloadDiff: [
                    'refund_number' => $refundNumber,
                    'deposit_number' => $lockedDeposit->deposit_number,
                    'amount' => $amount,
                    'currency' => $lockedDeposit->currency->value,
                    'reason' => $reason,
                ],
                status: 'SUCCESS',
                reason: "طلب استرداد دفعة مالية بمبلغ {$amount} {$lockedDeposit->currency->value}"
            );

            // 4. Call gateway refund API
            $gateway = $this->gatewayManager->getGateway($lockedDeposit->provider);
            $result = $gateway->refund($refund);

            if (!$result->isSuccessful()) {
                $refund->update([
                    'status' => PaymentRefundStatus::FAILED,
                    'metadata' => ['error' => $result->message(), 'raw' => $result->raw()],
                ]);

                AuditService::log(
                    action: AuditAction::PAYMENT_REFUND_FAILED,
                    userId: $performer?->id ?? $lockedDeposit->user_id,
                    entityType: PaymentRefund::class,
                    entityId: $refund->id,
                    payloadDiff: ['error' => $result->message()],
                    status: 'FAILED',
                    reason: "فشل استرداد المبلغ لدى المزود: {$result->message()}"
                );

                throw new RuntimeException("فشل تنفيذ الاسترداد لدى مزود الدفع: {$result->message()}");
            }

            // 5. Gateway confirmed refund: Post double-entry reverse transaction
            // Dr. 2010_BRAND_WALLET_LIABILITY (Deducts available balance from customer wallet)
            // Cr. 1010_PLATFORM_CASH (Deducts cash returned from platform asset)
            $ledgerEntries = [
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $lockedWallet->id,
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => $amount,
                    'description' => "خصم استرداد دفعة مالية من محفظة المتجر [{$refundNumber}]",
                ],
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => $amount,
                    'description' => "خروج نقدية مستردة للعميل من الحساب الوسيط عبر [{$lockedDeposit->provider->label()}] عملية [{$refundNumber}]",
                ],
            ];

            $ledgerTx = $this->ledgerService->postTransaction([
                'currency' => $lockedDeposit->currency,
                'transaction_type' => LedgerTransactionType::REVERSAL,
                'idempotency_key' => "LEDGER_REFUND_{$refundNumber}",
                'description' => "قيد استرداد مالي [{$refundNumber}] للعملية [{$lockedDeposit->deposit_number}]",
                'source_type' => PaymentRefund::class,
                'source_id' => $refund->id,
                'entries' => $ledgerEntries,
                'metadata' => [
                    'refund_number' => $refundNumber,
                    'deposit_number' => $lockedDeposit->deposit_number,
                    'provider' => $lockedDeposit->provider->value,
                    'provider_refund_id' => $result->transactionId(),
                ],
                'created_by' => $performer,
            ]);

            // Update refund record
            $refund->update([
                'status' => PaymentRefundStatus::SUCCEEDED,
                'provider_refund_id' => $result->transactionId(),
                'ledger_transaction_id' => $ledgerTx->id,
                'processed_at' => now(),
            ]);

            // Update deposit status: if total refunded equals full amount -> REFUNDED, else PARTIALLY_REFUNDED
            $newRemaining = $lockedDeposit->remainingRefundableAmount();
            $newDepositStatus = MoneyMath::isZero($newRemaining)
                ? PaymentDepositStatus::REFUNDED
                : PaymentDepositStatus::PARTIALLY_REFUNDED;

            $lockedDeposit->update([
                'status' => $newDepositStatus,
            ]);

            AuditService::log(
                action: AuditAction::PAYMENT_REFUND_SUCCEEDED,
                userId: $performer?->id ?? $lockedDeposit->user_id,
                entityType: PaymentRefund::class,
                entityId: $refund->id,
                payloadDiff: [
                    'refund_number' => $refundNumber,
                    'amount' => $amount,
                    'new_deposit_status' => $newDepositStatus->value,
                    'ledger_tx_id' => $ledgerTx->id,
                ],
                status: 'SUCCESS',
                reason: "اكتمال استرداد مالي وترحيل قيده المحاسبي بنجاح بمبلغ {$amount} {$lockedDeposit->currency->value}"
            );

            return $refund;
        });
    }

    /**
     * Reconcile deposit against provider records without silent mutations.
     * Returns structured comparison report.
     */
    public function reconcileWithProvider(PaymentDeposit $deposit): array
    {
        $gateway = $this->gatewayManager->getGateway($deposit->provider);
        $providerResult = $gateway->checkStatus($deposit);

        $discrepancies = [];

        // 1. Status comparison
        if ($providerResult->status() !== $deposit->status) {
            $discrepancies[] = [
                'field' => 'status',
                'internal' => $deposit->status->value,
                'provider' => $providerResult->status()->value,
                'description' => "اختلاف في الحالة: النظام الداخلي [{$deposit->status->label()}] بينما المزود [{$providerResult->status()->label()}]",
            ];
        }

        // 2. Amount comparison
        if ($providerResult->amount() !== null && MoneyMath::comp($providerResult->amount(), $deposit->amount) !== 0) {
            $discrepancies[] = [
                'field' => 'amount',
                'internal' => $deposit->amount,
                'provider' => $providerResult->amount(),
                'description' => "اختلاف في المبلغ: النظام الداخلي [{$deposit->amount}] بينما المزود [{$providerResult->amount()}]",
            ];
        }

        // 3. Currency comparison
        if ($providerResult->currency() !== null && strtoupper($providerResult->currency()) !== $deposit->currency->value) {
            $discrepancies[] = [
                'field' => 'currency',
                'internal' => $deposit->currency->value,
                'provider' => $providerResult->currency(),
                'description' => "اختلاف في العملة: النظام الداخلي [{$deposit->currency->value}] بينما المزود [{$providerResult->currency()}]",
            ];
        }

        $isMatched = empty($discrepancies);

        AuditService::log(
            action: AuditAction::PAYMENT_RECONCILIATION_RUN,
            userId: auth()->id(),
            entityType: PaymentDeposit::class,
            entityId: $deposit->id,
            payloadDiff: [
                'deposit_number' => $deposit->deposit_number,
                'is_matched' => $isMatched,
                'discrepancies_count' => count($discrepancies),
            ],
            status: $isMatched ? 'SUCCESS' : 'FAILED',
            reason: $isMatched ? "مطابقة مالية متطابقة 100% للعملية {$deposit->deposit_number}" : "اكتشاف فروقات أثناء المطابقة المالية للعملية {$deposit->deposit_number}"
        );

        return [
            'deposit_id' => $deposit->id,
            'deposit_number' => $deposit->deposit_number,
            'provider' => $deposit->provider->value,
            'internal_status' => $deposit->status->value,
            'provider_status' => $providerResult->status()->value,
            'internal_amount' => $deposit->amount,
            'provider_amount' => $providerResult->amount(),
            'currency' => $deposit->currency->value,
            'is_matched' => $isMatched,
            'discrepancies' => $discrepancies,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Recursively sanitize payload to mask sensitive fields like secret, token, key, card data.
     */
    public function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'secret', 'api_key', 'key', 'password', 'token', 'authorization',
            'hash', 'signature', 'cvv', 'cvc', 'card_number', 'pan',
        ];

        $sanitized = [];
        foreach ($payload as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
