<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerSubscription extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'subscription_number',
        'user_id',
        'marketer_profile_id',
        'subscription_plan_id',
        'status',
        'currency',
        'price_paid',
        'billing_cycle',
        'duration_in_days',
        'plan_snapshot',
        'starts_at',
        'ends_at',
        'payment_method',
        'payment_reference',
        'payment_deposit_id',
        'ledger_transaction_id',
        'cancelled_at',
        'cancellation_reason',
        'suspended_at',
        'suspension_reason',
        'idempotency_key',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'currency' => Currency::class,
            'billing_cycle' => BillingCycle::class,
            'price_paid' => 'decimal:2',
            'duration_in_days' => 'integer',
            'plan_snapshot' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'suspended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marketerProfile(): BelongsTo
    {
        return $this->belongsTo(MarketerProfile::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function ledgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        if ($this->status !== SubscriptionStatus::ACTIVE) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        if ($this->status === SubscriptionStatus::EXPIRED) {
            return true;
        }

        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function daysRemaining(): int
    {
        if (!$this->ends_at || $this->ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->ends_at, false);
    }

    /**
     * Check if this subscription entitles the marketer to premium campaigns.
     * Evaluated strictly from the immutable plan snapshot first, then fallback to plan model.
     */
    public function allowsPremiumCampaigns(): bool
    {
        if (isset($this->plan_snapshot['allows_premium_campaigns'])) {
            return (bool) $this->plan_snapshot['allows_premium_campaigns'];
        }

        return (bool) $this->plan?->allows_premium_campaigns;
    }

    /**
     * Get an entitlement attribute from the immutable snapshot.
     */
    public function entitlement(string $key, mixed $default = null): mixed
    {
        return $this->plan_snapshot[$key] ?? $default;
    }
}
