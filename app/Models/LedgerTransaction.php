<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\LedgerTransactionStatus;
use App\Enums\LedgerTransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LedgerTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'transaction_number',
        'currency',
        'transaction_type',
        'status',
        'source_type',
        'source_id',
        'idempotency_key',
        'description',
        'metadata',
        'created_by',
        'posted_at',
        'reversed_transaction_id',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'transaction_type' => LedgerTransactionType::class,
            'status' => LedgerTransactionStatus::class,
            'metadata' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * Enforce strict ledger transaction immutability:
     * Posted financial transactions can NEVER be deleted.
     * Core fields (currency, transaction_number, idempotency_key) cannot be altered.
     */
    protected static function booted(): void
    {
        static::updating(function (LedgerTransaction $tx) {
            $immutableFields = ['transaction_number', 'currency', 'transaction_type', 'idempotency_key', 'posted_at'];
            foreach ($immutableFields as $field) {
                if ($tx->isDirty($field)) {
                    throw new \RuntimeException("الحقل المالي المحاسبي [{$field}] غير قابل للتعديل بعد الترحيل.");
                }
            }
        });

        static::deleting(function () {
            throw new \RuntimeException('المعاملات المالية المرحلة في دفتر الأستاذ غير قابلة للحذف نهائياً لضمان النزاهة والمطابقة.');
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function debitEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class)->where('entry_type', 'DEBIT');
    }

    public function creditEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class)->where('entry_type', 'CREDIT');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'reversed_transaction_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(LedgerTransaction::class, 'reversed_transaction_id');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', LedgerTransactionStatus::POSTED);
    }

    public function isPosted(): bool
    {
        return $this->status === LedgerTransactionStatus::POSTED;
    }

    public function isReversed(): bool
    {
        return $this->status === LedgerTransactionStatus::REVERSED;
    }

    /**
     * Check if total debits equal total credits.
     */
    public function isBalanced(): bool
    {
        $debits = '0.0000';
        $credits = '0.0000';

        foreach ($this->entries as $entry) {
            if ($entry->isDebit()) {
                $debits = \App\Services\Math\MoneyMath::add($debits, $entry->amount);
            } else {
                $credits = \App\Services\Math\MoneyMath::add($credits, $entry->amount);
            }
        }

        return \App\Services\Math\MoneyMath::comp($debits, $credits) === 0;
    }
}
