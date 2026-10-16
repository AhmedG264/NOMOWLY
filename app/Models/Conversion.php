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
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversionStatus::class,
            'order_value' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'occurred_at' => 'datetime',
            'verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignApplication(): BelongsTo
    {
        return $this->belongsTo(CampaignApplication::class);
    }

    public function attributionClick(): BelongsTo
    {
        return $this->belongsTo(AttributionClick::class);
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
}
