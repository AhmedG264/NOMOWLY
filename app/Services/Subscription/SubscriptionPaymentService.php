<?php

namespace App\Services\Subscription;

use App\Enums\AuditAction;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionType;
use App\Enums\SubscriptionStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\MarketerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AuditService;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SubscriptionPaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected LedgerService $ledgerService,
        protected WalletService $walletService,
        protected SubscriptionEntitlementService $entitlementService
    ) {}

    /**
     * Initiate a new subscription purchase request.
     *
     * @param array{
     *     user: User,
     *     plan: SubscriptionPlan,
     *     currency: Currency|string,
     *     payment_method?: string,
     *     idempotency_key?: string
     * } $data
     */
    public function initiateSubscription(array $data): MarketerSubscription
    {
        $user = $data['user'];
        /** @var SubscriptionPlan $plan */
        $plan = $data['plan'];
        $currency = $data['currency'] instanceof Currency ? $data['currency'] : Currency::from(strtoupper(trim($data['currency'])));
        $paymentMethod = strtoupper(trim($data['payment_method'] ?? 'GATEWAY'));

        if (!$plan->isActive()) {
            throw new RuntimeException("خطة الاشتراك [{$plan->name_ar}] معطلة حالياً ولا يمكن الاشتراك بها.");
        }

        $profile = $user->marketerProfile;
        if (!$profile) {
            throw new RuntimeException('يجب إكمال الملف التعريفي للمسوق قبل الاشتراك في الباقات.');
        }

        // Strict currency pricing: find price configured for this exact currency
        $planPrice = $plan->priceFor($currency);
        if (!$planPrice) {
            throw new InvalidArgumentException("خطة الاشتراك [{$plan->name_ar}] غير مسعّرة بالعملة [{$currency->value}]. لا يتم تحويل العملات تلقائياً.");
        }

        $pricePaid = MoneyMath::format($planPrice->price);
        $snapshot = $plan->buildSnapshot($currency);
        $durationDays = $plan->duration_in_days ?: 30;

        $subNumber = 'SUB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        $idempotencyKey = $data['idempotency_key'] ?? ('SUB_' . $user->id . '_' . $plan->id . '_' . microtime(true) . '_' . Str::random(6));

        return DB::transaction(function () use (
            $user,
            $profile,
            $plan,
            $currency,
            $pricePaid,
            $durationDays,
            $snapshot,
            $paymentMethod,
            $subNumber,
            $idempotencyKey
        ) {
            $subscription = MarketerSubscription::create([
                'subscription_number' => $subNumber,
                'user_id' => $user->id,
                'marketer_profile_id' => $profile->id,
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::PENDING_PAYMENT,
                'currency' => $currency,
                'price_paid' => $pricePaid,
                'billing_cycle' => $plan->billing_cycle,
                'duration_in_days' => $durationDays,
                'plan_snapshot' => $snapshot,
                'payment_method' => $paymentMethod,
                'idempotency_key' => $idempotencyKey,
            ]);

            AuditService::log(
                action: AuditAction::SUBSCRIPTION_INITIATED,
                userId: $user->id,
                entityType: MarketerSubscription::class,
                entityId: $subscription->id,
                payloadDiff: [
                    'new' => [
                        'subscription_number' => $subscription->subscription_number,
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name_ar,
                        'price' => $pricePaid,
                        'currency' => $currency->value,
                        'payment_method' => $paymentMethod,
                    ],
                ],
                status: 'SUCCESS',
                reason: "بدء طلب اشتراك في باقة [{$plan->name_ar}] بمبلغ {$pricePaid} {$currency->value}"
            );

            return $subscription;
        });
    }

    /**
     * Confirm subscription activation via direct Payment Gateway.
     * Strictly idempotent: replaying a webhook or confirming twice is safe.
     */
    public function confirmGatewayPayment(
        MarketerSubscription $subscription,
        string $paymentReference,
        ?string $confirmedFee = null,
        array $extraMetadata = []
    ): MarketerSubscription {
        return DB::transaction(function () use ($subscription, $paymentReference, $confirmedFee, $extraMetadata) {
            /** @var MarketerSubscription $lockedSub */
            $lockedSub = MarketerSubscription::where('id', $subscription->id)->lockForUpdate()->firstOrFail();

            // Idempotency: If already ACTIVE, return immediately
            if ($lockedSub->status === SubscriptionStatus::ACTIVE) {
                return $lockedSub;
            }

            $amount = MoneyMath::format($lockedSub->price_paid);
            $currency = $lockedSub->currency;
            $currencyCode = $currency->value;
            $actualFee = $confirmedFee !== null ? MoneyMath::format($confirmedFee) : '0.0000';

            // Balanced Double-Entry Ledger Posting:
            // Dr. 1010_PLATFORM_CASH (net bank cash reaching platform)
            // Dr. 5030_PAYMENT_GATEWAY_FEE_EXPENSE (fee if any)
            // Cr. 4020_SUBSCRIPTION_REVENUE (subscription operational revenue)
            $entries = [];

            if (MoneyMath::isPositive($actualFee)) {
                $netCash = MoneyMath::sub($amount, $actualFee);
                $entries = [
                    [
                        'account_code' => '1010_PLATFORM_CASH',
                        'entry_type' => LedgerEntryType::DEBIT,
                        'amount' => $netCash,
                        'description' => "صافي نقدية محصلة لاشتراك باقة [{$lockedSub->subscription_number}] بعد استقطاع رسوم البوابة",
                    ],
                    [
                        'account_code' => '5030_PAYMENT_GATEWAY_FEE_EXPENSE',
                        'entry_type' => LedgerEntryType::DEBIT,
                        'amount' => $actualFee,
                        'description' => "مصروف رسوم بوابة الدفع لاشتراك باقة [{$lockedSub->subscription_number}]",
                    ],
                    [
                        'account_code' => '4020_SUBSCRIPTION_REVENUE',
                        'entry_type' => LedgerEntryType::CREDIT,
                        'amount' => $amount,
                        'description' => "إيراد اشتراك باقة مسوق [{$lockedSub->subscription_number}]",
                    ],
                ];
            } else {
                $entries = [
                    [
                        'account_code' => '1010_PLATFORM_CASH',
                        'entry_type' => LedgerEntryType::DEBIT,
                        'amount' => $amount,
                        'description' => "نقدية محصلة لاشتراك باقة [{$lockedSub->subscription_number}]",
                    ],
                    [
                        'account_code' => '4020_SUBSCRIPTION_REVENUE',
                        'entry_type' => LedgerEntryType::CREDIT,
                        'amount' => $amount,
                        'description' => "إيراد اشتراك باقة مسوق [{$lockedSub->subscription_number}]",
                    ],
                ];
            }

            $ledgerTx = $this->ledgerService->postTransaction([
                'currency' => $currency,
                'transaction_type' => LedgerTransactionType::SUBSCRIPTION_PAYMENT,
                'idempotency_key' => "SUB_GATEWAY_{$lockedSub->subscription_number}",
                'description' => "سداد إيراد اشتراك باقة مسوق [{$lockedSub->subscription_number}]",
                'source_type' => MarketerSubscription::class,
                'source_id' => $lockedSub->id,
                'entries' => $entries,
                'metadata' => [
                    'subscription_number' => $lockedSub->subscription_number,
                    'payment_reference' => $paymentReference,
                    'extra' => $extraMetadata,
                ],
                'created_by' => $lockedSub->user,
            ]);

            $startsAt = now();
            $endsAt = now()->addDays($lockedSub->duration_in_days);

            $lockedSub->update([
                'status' => SubscriptionStatus::ACTIVE,
                'payment_reference' => $paymentReference,
                'ledger_transaction_id' => $ledgerTx->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'metadata' => array_merge($lockedSub->metadata ?? [], [
                    'confirmed_at' => now()->toIso8601String(),
                    'ledger_transaction_number' => $ledgerTx->transaction_number,
                    'extra' => $extraMetadata,
                ]),
            ]);

            AuditService::log(
                action: AuditAction::SUBSCRIPTION_ACTIVATED,
                userId: $lockedSub->user_id,
                entityType: MarketerSubscription::class,
                entityId: $lockedSub->id,
                payloadDiff: [
                    'subscription_number' => $lockedSub->subscription_number,
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $endsAt->toIso8601String(),
                    'amount' => $amount,
                    'currency' => $currencyCode,
                    'payment_method' => 'GATEWAY',
                ],
                status: 'SUCCESS',
                reason: "تفعيل اشتراك المسوق بالباقة حتى تاريخ {$endsAt->format('Y-m-d')}"
            );

            return $lockedSub;
        });
    }

    /**
     * Pay and activate subscription directly using Marketer's earned wallet balance.
     * Decreases marketer payable liability and records subscription revenue.
     */
    public function payWithWalletBalance(MarketerSubscription $subscription): MarketerSubscription
    {
        return DB::transaction(function () use ($subscription) {
            /** @var MarketerSubscription $lockedSub */
            $lockedSub = MarketerSubscription::where('id', $subscription->id)->lockForUpdate()->firstOrFail();

            if ($lockedSub->status === SubscriptionStatus::ACTIVE) {
                return $lockedSub;
            }

            $user = $lockedSub->user;
            $currency = $lockedSub->currency;
            $amount = MoneyMath::format($lockedSub->price_paid);

            // Fetch and lock marketer's wallet for this currency
            $wallet = $this->walletService->getOrCreateWallet($user, $currency);
            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if (MoneyMath::comp($lockedWallet->available_balance, $amount) < 0) {
                throw new InsufficientFundsException(
                    "رصيد أرباحك المتاح في المحفظة [{$lockedWallet->available_balance} {$currency->value}] غير كافٍ لسداد رسوم الاشتراك [{$amount} {$currency->value}]."
                );
            }

            // Balanced Double-Entry Ledger Posting:
            // Dr. 2030_MARKETER_PAYABLE_LIABILITY (wallet_id: marketer wallet) -> decreases marketer liability & available balance
            // Cr. 4020_SUBSCRIPTION_REVENUE -> recognizes platform subscription revenue
            $entries = [
                [
                    'account_code' => '2030_MARKETER_PAYABLE_LIABILITY',
                    'wallet_id' => $lockedWallet->id,
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => $amount,
                    'description' => "خصم رسوم اشتراك باقة [{$lockedSub->subscription_number}] من رصيد أرباح المسوق",
                ],
                [
                    'account_code' => '4020_SUBSCRIPTION_REVENUE',
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => $amount,
                    'description' => "إيراد اشتراك باقة مسوق مسدد من المحفظة [{$lockedSub->subscription_number}]",
                ],
            ];

            $ledgerTx = $this->ledgerService->postTransaction([
                'currency' => $currency,
                'transaction_type' => LedgerTransactionType::SUBSCRIPTION_PAYMENT,
                'idempotency_key' => "SUB_WALLET_{$lockedSub->subscription_number}",
                'description' => "سداد اشتراك باقة مسوق من رصيد الأرباح [{$lockedSub->subscription_number}]",
                'source_type' => MarketerSubscription::class,
                'source_id' => $lockedSub->id,
                'entries' => $entries,
                'metadata' => [
                    'subscription_number' => $lockedSub->subscription_number,
                    'wallet_id' => $lockedWallet->id,
                ],
                'created_by' => $user,
            ]);

            $startsAt = now();
            $endsAt = now()->addDays($lockedSub->duration_in_days);

            $lockedSub->update([
                'status' => SubscriptionStatus::ACTIVE,
                'payment_method' => 'WALLET',
                'payment_reference' => "WALLET-{$lockedWallet->id}",
                'ledger_transaction_id' => $ledgerTx->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'metadata' => array_merge($lockedSub->metadata ?? [], [
                    'confirmed_at' => now()->toIso8601String(),
                    'ledger_transaction_number' => $ledgerTx->transaction_number,
                ]),
            ]);

            AuditService::log(
                action: AuditAction::SUBSCRIPTION_ACTIVATED,
                userId: $user->id,
                entityType: MarketerSubscription::class,
                entityId: $lockedSub->id,
                payloadDiff: [
                    'subscription_number' => $lockedSub->subscription_number,
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $endsAt->toIso8601String(),
                    'amount' => $amount,
                    'currency' => $currency->value,
                    'payment_method' => 'WALLET',
                ],
                status: 'SUCCESS',
                reason: "سداد وتفعيل اشتراك المسوق من رصيد المحفظة حتى تاريخ {$endsAt->format('Y-m-d')}"
            );

            return $lockedSub;
        });
    }

    /**
     * Mark an expired subscription as EXPIRED without revoking historical rights or earnings.
     */
    public function expireSubscription(MarketerSubscription $subscription): MarketerSubscription
    {
        if ($subscription->status === SubscriptionStatus::EXPIRED) {
            return $subscription;
        }

        $subscription->update([
            'status' => SubscriptionStatus::EXPIRED,
        ]);

        AuditService::log(
            action: AuditAction::SUBSCRIPTION_EXPIRED,
            userId: $subscription->user_id,
            entityType: MarketerSubscription::class,
            entityId: $subscription->id,
            payloadDiff: [
                'subscription_number' => $subscription->subscription_number,
                'status' => SubscriptionStatus::EXPIRED->value,
                'ended_at' => $subscription->ends_at?->toIso8601String(),
            ],
            status: 'SUCCESS',
            reason: 'انتهاء صلاحية باقة الاشتراك دون المساس بالمشاركات القائمة أو الأرباح التاريخية'
        );

        return $subscription;
    }

    /**
     * Suspend a subscription administratively with a mandatory reason.
     */
    public function suspendSubscription(MarketerSubscription $subscription, User $admin, string $reason): MarketerSubscription
    {
        if (empty(trim($reason))) {
            throw new InvalidArgumentException('يجب تقديم سبب إلزامي لتعليق الاشتراك.');
        }

        $subscription->update([
            'status' => SubscriptionStatus::SUSPENDED,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        AuditService::log(
            action: AuditAction::SUBSCRIPTION_SUSPENDED,
            userId: $admin->id,
            entityType: MarketerSubscription::class,
            entityId: $subscription->id,
            payloadDiff: [
                'subscription_number' => $subscription->subscription_number,
                'status' => SubscriptionStatus::SUSPENDED->value,
                'suspended_at' => now()->toIso8601String(),
                'reason' => $reason,
            ],
            status: 'SUCCESS',
            reason: "تعليق اشتراك المسوق إدارياً: {$reason}"
        );

        return $subscription;
    }

    /**
     * Cancel an active subscription upon user or admin request.
     */
    public function cancelSubscription(MarketerSubscription $subscription, User $actor, string $reason): MarketerSubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        AuditService::log(
            action: AuditAction::SUBSCRIPTION_CANCELLED,
            userId: $actor->id,
            entityType: MarketerSubscription::class,
            entityId: $subscription->id,
            payloadDiff: [
                'subscription_number' => $subscription->subscription_number,
                'status' => SubscriptionStatus::CANCELLED->value,
                'cancelled_at' => now()->toIso8601String(),
                'reason' => $reason,
            ],
            status: 'SUCCESS',
            reason: "إلغاء اشتراك المسوق: {$reason}"
        );

        return $subscription;
    }
}
