<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentRefundStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRefund extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'refund_number',
        'deposit_id',
        'user_id',
        'amount',
        'currency',
        'reason',
        'status',
        'provider_refund_id',
        'idempotency_key',
        'metadata',
        'ledger_transaction_id',
        'created_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'status' => PaymentRefundStatus::class,
            'amount' => 'decimal:4',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(PaymentDeposit::class, 'deposit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSuccess(): bool
    {
        return $this->status === PaymentRefundStatus::SUCCEEDED;
    }
}
