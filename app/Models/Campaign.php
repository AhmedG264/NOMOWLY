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
        'is_premium',
        'allowed_plan_ids',
        'premium_requirements',
        'badge',
        'brand_guidelines',
        'starts_at',
        'ends_at',
        'current_terms_version_id',
        'is_terms_locked',
        'terms_locked_at',
        'terms_lock_reason',
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
            'is_premium' => 'boolean',
            'allowed_plan_ids' => 'array',
            'cookie_duration_days' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_terms_locked' => 'boolean',
            'terms_locked_at' => 'datetime',
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

    public function termsVersions(): HasMany
    {
        return $this->hasMany(CampaignTermsVersion::class)->orderBy('version_number', 'asc');
    }

    public function currentTermsVersion(): BelongsTo
    {
        return $this->belongsTo(CampaignTermsVersion::class, 'current_terms_version_id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(CampaignTermsChangeRequest::class)->latest();
    }

    public function pendingChangeRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CampaignTermsChangeRequest::class)
            ->where('status', \App\Enums\CampaignTermsChangeRequestStatus::PENDING);
    }

    /**
     * Check if terms are strictly locked from direct modification
     */
    public function isTermsLocked(): bool
    {
        if ($this->is_terms_locked) {
            return true;
        }

        // Dynamically verify if any binding activity exists
        if ($this->hasActiveMarketActivity()) {
            $this->lockTerms('تم تثبيت الشروط التعاقدية تلقائياً لوجود مسوقين معتمدين أو نشاط إسناد مسجل.');
            return true;
        }

        return false;
    }

    /**
     * Check if campaign has active approved marketers, clicks, or conversions
     */
    public function hasActiveMarketActivity(): bool
    {
        return $this->approvedApplications()->exists()
            || $this->clicks()->exists()
            || $this->conversions()->exists();
    }

    /**
     * Atomically lock terms
     */
    public function lockTerms(string $reason = 'تم تثبيت الشروط التعاقدية للحملة لحماية حقوق المسوقين.'): void
    {
        if (!$this->is_terms_locked) {
            $this->update([
                'is_terms_locked' => true,
                'terms_locked_at' => now(),
                'terms_lock_reason' => $reason,
            ]);

            \App\Services\AuditService::log(
                action: \App\Enums\AuditAction::CAMPAIGN_TERMS_LOCKED,
                userId: auth()->id(),
                entityType: 'Campaign',
                entityId: $this->id,
                payloadDiff: [
                    'campaign_id' => $this->id,
                    'current_version_id' => $this->current_terms_version_id,
                    'reason' => $reason,
                ],
                reason: $reason
            );
        }
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

    public function isPremium(): bool
    {
        return (bool) $this->is_premium;
    }

    public function allowsPlan(?string $planId): bool
    {
        if (!$this->is_premium) {
            return true;
        }

        if (empty($this->allowed_plan_ids)) {
            return true; // Any premium subscription plan is allowed
        }

        return $planId !== null && in_array($planId, (array) $this->allowed_plan_ids, true);
    }
}
