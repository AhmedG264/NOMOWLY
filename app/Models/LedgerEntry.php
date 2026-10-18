<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class LedgerEntry extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ledger_transaction_id',
        'ledger_account_id',
        'wallet_id',
        'entry_type',
        'amount',
        'currency',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => LedgerEntryType::class,
            'currency' => Currency::class,
            'amount' => 'decimal:4',
        ];
    }

    /**
     * Enforce strict immutability: posted ledger entries can NEVER be updated or deleted.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('القيود المحاسبية في دفتر الأستاذ غير قابلة للتعديل نهائياً. التصحيح يتم حصراً بقيد عكسي.');
        });

        static::deleting(function () {
            throw new RuntimeException('القيود المحاسبية في دفتر الأستاذ غير قابلة للحذف نهائياً لضمان النزاهة والمطابقة المالية.');
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function isDebit(): bool
    {
        return $this->entry_type === LedgerEntryType::DEBIT;
    }

    public function isCredit(): bool
    {
        return $this->entry_type === LedgerEntryType::CREDIT;
    }
}
