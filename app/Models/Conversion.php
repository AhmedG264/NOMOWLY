<?php

namespace App\Models;

use App\Enums\ConversionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversion extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'campaign_id',
        'campaign_application_id',
        'attribution_click_id',
        'terms_version_id',
        'visitor_id',
        'external_order_id',
        'conversion_type',
        'status',
        'order_value',
        'currency',
        'commission_value',
        'rejection_reason',
        'occurred_at',
        'verified_at',
        'metadata',
        'commission_terms_snapshot',
        'fraud_risk_score',
        'is_suspicious',
        'fraud_signals',
        'investigated_at',
        'investigated_by_user_id',
        'investigation_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversionStatus::class,
            'order_value' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'occurred_at' => 'datetime',
            'verified_at' => 'datetime',
            'investigated_at' => 'datetime',
            'metadata' => 'array',
            'commission_terms_snapshot' => 'array',
            'fraud_signals' => 'array',
            'is_suspicious' => 'boolean',
            'fraud_risk_score' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function termsVersion(): BelongsTo
    {
        return $this->belongsTo(CampaignTermsVersion::class, 'terms_version_id');
    }

    public function campaignApplication(): BelongsTo
    {
        return $this->belongsTo(CampaignApplication::class);
    }

    public function attributionClick(): BelongsTo
    {
        return $this->belongsTo(AttributionClick::class);
    }

    public function investigatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by_user_id');
    }

    public function settlement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CommissionSettlement::class);
    }

    public function isSettled(): bool
    {
        return $this->settlement()->exists();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ConversionStatus::APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ConversionStatus::PENDING);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', ConversionStatus::REJECTED);
    }

    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', true);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', ConversionStatus::PENDING)
            ->where(function ($q) {
                $q->where('is_suspicious', true)
                  ->orWhere('fraud_risk_score', '>=', 40);
            });
    }

    public function isApproved(): bool
    {
        return $this->status === ConversionStatus::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === ConversionStatus::PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === ConversionStatus::REJECTED;
    }

    public function isSuspicious(): bool
    {
        return (bool) $this->is_suspicious;
    }
}
