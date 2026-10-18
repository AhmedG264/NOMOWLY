<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class WalletTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'wallet_id',
        'ledger_transaction_id',
        'ledger_entry_id',
        'entry_type',
        'amount',
        'balance_after',
        'reserved_after',
        'currency',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => LedgerEntryType::class,
            'currency' => Currency::class,
            'amount' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'reserved_after' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('حركات كشف حساب المحفظة غير قابلة للتعديل نهائياً.');
        });

        static::deleting(function () {
            throw new RuntimeException('حركات كشف حساب المحفظة غير قابلة للحذف نهائياً.');
        });
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function ledgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
