<?php

namespace App\Models;

use App\Enums\CampaignCommissionType;
use App\Enums\CampaignTermsVersionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignTermsVersion extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'campaign_id',
        'version_number',
        'status',
        'title',
        'description',
        'brief',
        'commission_type',
        'commission_value',
        'currency',
        'currencies',
        'cookie_duration_days',
        'conversion_qualification_rules',
        'approval_rules',
        'cancellation_refund_rules',
        'effective_from',
        'effective_until',
        'change_summary',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignTermsVersionStatus::class,
            'commission_type' => CampaignCommissionType::class,
            'currencies' => 'array',
            'version_number' => 'integer',
            'cookie_duration_days' => 'integer',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CampaignApplication::class, 'terms_version_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AttributionClick::class, 'terms_version_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class, 'terms_version_id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(CampaignTermsChangeRequest::class, 'current_version_id');
    }

    public function isActive(): bool
    {
        return $this->status === CampaignTermsVersionStatus::ACTIVE;
    }

    public function isSuperseded(): bool
    {
        return $this->status === CampaignTermsVersionStatus::SUPERSEDED;
    }

    /**
     * Return a standardized array snapshot of terms
     */
    public function toTermsArray(): array
    {
        return [
            'version_number' => $this->version_number,
            'title' => $this->title,
            'description' => $this->description,
            'commission_type' => $this->commission_type instanceof CampaignCommissionType ? $this->commission_type->value : $this->commission_type,
            'commission_value' => (string) $this->commission_value,
            'currency' => $this->currency,
            'currencies' => $this->currencies,
            'cookie_duration_days' => (int) $this->cookie_duration_days,
            'conversion_qualification_rules' => $this->conversion_qualification_rules,
            'approval_rules' => $this->approval_rules,
            'cancellation_refund_rules' => $this->cancellation_refund_rules,
            'effective_from' => $this->effective_from?->toIso8601String(),
            'effective_until' => $this->effective_until?->toIso8601String(),
        ];
    }
}
