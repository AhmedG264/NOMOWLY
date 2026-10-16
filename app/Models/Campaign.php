<?php

namespace App\Models;

use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\CampaignApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'brand_profile_id',
        'title',
        'slug',
        'destination_url',
        'category',
        'category_slug',
        'description',
        'brief',
        'requirements',
        'target_markets',
        'currencies',
        'commission_type',
        'commission_value',
        'avg_basket',
        'conversion_rate',
        'cookie_duration_days',
        'tracking_methods',
        'status',
        'is_featured',
        'badge',
        'brand_guidelines',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'target_markets' => 'array',
            'currencies' => 'array',
            'tracking_methods' => 'array',
            'status' => CampaignStatus::class,
            'commission_type' => CampaignCommissionType::class,
            'is_featured' => 'boolean',
            'cookie_duration_days' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (empty($campaign->slug)) {
                $baseSlug = Str::slug($campaign->title);
                if (empty($baseSlug)) {
                    $baseSlug = 'campaign-' . Str::lower(Str::random(6));
                }
                $campaign->slug = $baseSlug . '-' . Str::lower(Str::random(5));
            }

            if (empty($campaign->category_slug)) {
                $campaign->category_slug = Str::slug($campaign->category);
            }
        });
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfile::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CampaignApplication::class);
    }

    public function approvedApplications(): HasMany
    {
        return $this->hasMany(CampaignApplication::class)->where('status', CampaignApplicationStatus::APPROVED);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AttributionClick::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::ACTIVE);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Filter campaigns in marketplace
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['category'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('category_slug', $filters['category'])
                  ->orWhere('category', $filters['category']);
            });
        }

        if (!empty($filters['country'])) {
            $country = $filters['country'];
            $query->where('target_markets', 'like', "%{$country}%");
        }

        if (!empty($filters['commission_type'])) {
            $query->where('commission_type', $filters['commission_type']);
        }

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('brandProfile', function ($bq) use ($search) {
                      $bq->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    public function hasAppliedBy(?MarketerProfile $marketerProfile): bool
    {
        if (!$marketerProfile) {
            return false;
        }

        return $this->applications()
            ->where('marketer_profile_id', $marketerProfile->id)
            ->exists();
    }

    public function getApplicationFor(?MarketerProfile $marketerProfile): ?CampaignApplication
    {
        if (!$marketerProfile) {
            return null;
        }

        return $this->applications()
            ->where('marketer_profile_id', $marketerProfile->id)
            ->first();
    }
}
