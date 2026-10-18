<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Enums\SubscriptionPlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'billing_cycle',
        'duration_in_days',
        'status',
        'sort_order',
        'is_featured',
        'allows_premium_campaigns',
        'max_premium_campaigns_per_month',
        'max_active_applications',
        'features',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'status' => SubscriptionPlanStatus::class,
            'duration_in_days' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'allows_premium_campaigns' => 'boolean',
            'max_premium_campaigns_per_month' => 'integer',
            'max_active_applications' => 'integer',
            'features' => 'array',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(SubscriptionPlanPrice::class);
    }

    public function activePrices(): HasMany
    {
        return $this->hasMany(SubscriptionPlanPrice::class)->where('is_active', true);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MarketerSubscription::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionPlanStatus::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionPlanStatus::ACTIVE;
    }

    public function priceFor(Currency|string $currency): ?SubscriptionPlanPrice
    {
        $code = $currency instanceof Currency ? $currency->value : strtoupper(trim($currency));

        return $this->prices->firstWhere(function ($p) use ($code) {
            $currVal = $p->currency instanceof Currency ? $p->currency->value : (string) $p->currency;
            return $currVal === $code && $p->is_active;
        });
    }

    public function formattedPriceFor(Currency|string $currency): string
    {
        $price = $this->priceFor($currency);
        if (!$price) {
            return 'غير متوفر بالعملة المحددة';
        }

        $currCode = $price->currency instanceof Currency ? $price->currency->value : (string) $price->currency;
        return number_format((float) $price->price, 2) . ' ' . $currCode;
    }

    /**
     * Build an immutable snapshot of this plan for recording upon purchase.
     */
    public function buildSnapshot(Currency|string $currency): array
    {
        $currEnum = $currency instanceof Currency ? $currency : Currency::from(strtoupper(trim($currency)));
        $planPrice = $this->priceFor($currEnum);

        return [
            'plan_id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'billing_cycle' => $this->billing_cycle instanceof BillingCycle ? $this->billing_cycle->value : (string) $this->billing_cycle,
            'duration_in_days' => $this->duration_in_days,
            'currency' => $currEnum->value,
            'price' => $planPrice ? (string) $planPrice->price : '0.00',
            'allows_premium_campaigns' => (bool) $this->allows_premium_campaigns,
            'max_premium_campaigns_per_month' => $this->max_premium_campaigns_per_month,
            'max_active_applications' => $this->max_active_applications,
            'features' => $this->features ?? [],
            'snapshotted_at' => now()->toIso8601String(),
        ];
    }
}
