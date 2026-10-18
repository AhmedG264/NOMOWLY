<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionSettlement extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'conversion_id',
        'ledger_transaction_id',
        'brand_wallet_id',
        'marketer_wallet_id',
        'currency',
        'commission_amount',
        'platform_fee_amount',
        'settled_by',
        'settled_at',
        'reversal_ledger_transaction_id',
        'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'commission_amount' => 'decimal:4',
            'platform_fee_amount' => 'decimal:4',
            'settled_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    public function ledgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class);
    }

    public function reversalLedgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'reversal_ledger_transaction_id');
    }

    public function brandWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'brand_wallet_id');
    }

    public function marketerWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'marketer_wallet_id');
    }

    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function isReversed(): bool
    {
        return !is_null($this->reversed_at);
    }
}
