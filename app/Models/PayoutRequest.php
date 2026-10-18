<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PayoutRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRequest extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'request_number',
        'user_id',
        'wallet_id',
        'currency',
        'amount',
        'status',
        'bank_name',
        'account_holder_name',
        'account_reference',
        'admin_notes',
        'rejection_reason',
        'payment_reference',
        'idempotency_key',
        'hold_ledger_transaction_id',
        'payout_ledger_transaction_id',
        'reviewed_by',
        'reviewed_at',
        'paid_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'status' => PayoutRequestStatus::class,
            'amount' => 'decimal:4',
            'reviewed_at' => 'datetime',
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

    public function holdTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'hold_ledger_transaction_id');
    }

    public function payoutTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'payout_ledger_transaction_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PayoutRequestStatus::REQUESTED);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PayoutRequestStatus::APPROVED);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', PayoutRequestStatus::PAID);
    }

    public function isPending(): bool
    {
        return $this->status === PayoutRequestStatus::REQUESTED;
    }

    public function isApproved(): bool
    {
        return $this->status === PayoutRequestStatus::APPROVED;
    }

    public function isPaid(): bool
    {
        return $this->status === PayoutRequestStatus::PAID;
    }

    public function isRejected(): bool
    {
        return $this->status === PayoutRequestStatus::REJECTED;
    }
}
