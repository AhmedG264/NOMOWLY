<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentDeposit extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'deposit_number',
        'user_id',
        'wallet_id',
        'currency',
        'amount',
        'fee_amount',
        'net_amount',
        'provider',
        'provider_transaction_id',
        'payment_method',
        'status',
        'idempotency_key',
        'metadata',
        'failure_reason',
        'ledger_transaction_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'provider' => PaymentProvider::class,
            'status' => PaymentDepositStatus::class,
            'amount' => 'decimal:4',
            'fee_amount' => 'decimal:4',
            'net_amount' => 'decimal:4',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function ledgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class, 'deposit_id');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(PaymentWebhook::class, 'deposit_id');
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentDepositStatus::SUCCEEDED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentDepositStatus::PENDING || $this->status === PaymentDepositStatus::PROCESSING;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentDepositStatus::FAILED || $this->status === PaymentDepositStatus::CANCELLED;
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, [
            PaymentDepositStatus::PARTIALLY_REFUNDED,
            PaymentDepositStatus::REFUNDED,
        ], true);
    }

    /**
     * Compute total amount already refunded for this deposit.
     */
    public function refundedAmount(): string
    {
        $sum = $this->refunds()
            ->whereIn('status', [
                \App\Enums\PaymentRefundStatus::SUCCEEDED,
                \App\Enums\PaymentRefundStatus::REQUESTED,
                \App\Enums\PaymentRefundStatus::PROCESSING,
            ])
            ->sum('amount');

        return \App\Services\Math\MoneyMath::format($sum ?? '0');
    }

    /**
     * Compute remaining refundable amount.
     */
    public function remainingRefundableAmount(): string
    {
        $refunded = $this->refundedAmount();
        $amount = \App\Services\Math\MoneyMath::format($this->amount);

        return \App\Services\Math\MoneyMath::sub($amount, $refunded);
    }
}
