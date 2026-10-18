<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\WalletStatus;
use App\Enums\WalletType;
use App\Services\Math\MoneyMath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'wallet_type',
        'currency',
        'available_balance',
        'reserved_balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'wallet_type' => WalletType::class,
            'currency' => Currency::class,
            'status' => WalletStatus::class,
            'available_balance' => 'decimal:4',
            'reserved_balance' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->orderBy('created_at', 'desc');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class)->orderBy('created_at', 'desc');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', WalletStatus::ACTIVE);
    }

    public function scopeCurrency(Builder $query, string|Currency $currency): Builder
    {
        $code = $currency instanceof Currency ? $currency->value : $currency;
        return $query->where('currency', $code);
    }

    /**
     * Total balance = available_balance + reserved_balance
     */
    public function getTotalBalanceAttribute(): string
    {
        return MoneyMath::add($this->available_balance ?? '0', $this->reserved_balance ?? '0');
    }

    /**
     * Alias for total_balance
     */
    public function getBalanceAttribute(): string
    {
        return $this->total_balance;
    }

    /**
     * Check if wallet is active.
     */
    public function isActive(): bool
    {
        return $this->status === WalletStatus::ACTIVE;
    }
}
