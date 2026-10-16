<?php

namespace App\Models;

use App\Enums\CampaignApplicationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CampaignApplication extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'campaign_id',
        'marketer_profile_id',
        'status',
        'pitch_note',
        'channel_urls',
        'tracking_code',
        'tracking_link',
        'review_notes',
        'applied_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'channel_urls' => 'array',
            'status' => CampaignApplicationStatus::class,
            'applied_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function marketerProfile(): BelongsTo
    {
        return $this->belongsTo(MarketerProfile::class);
    }

    public function isPending(): bool
    {
        return $this->status === CampaignApplicationStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === CampaignApplicationStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === CampaignApplicationStatus::REJECTED;
    }

    public function clicks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttributionClick::class);
    }

    public function conversions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    /**
     * Generate a default tracking code and smart URL for an approved application
     */
    public function generateTrackingCredentials(): void
    {
        // Enforce that only approved applications can receive active tracking credentials
        if ($this->status !== CampaignApplicationStatus::APPROVED) {
            return;
        }

        if (empty($this->tracking_code)) {
            $brandInitials = Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->campaign?->brandProfile?->company_name ?? 'NMW'), 0, 4));
            if (empty($brandInitials)) {
                $brandInitials = 'NMW';
            }
            $marketerInitials = Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->marketerProfile?->full_name ?? 'CREATOR'), 0, 4));
            if (empty($marketerInitials)) {
                $marketerInitials = 'PARTNER';
            }

            // Cryptographically secure random suffix with database uniqueness check
            do {
                $randomSuffix = Str::upper(bin2hex(random_bytes(3)));
                $candidate = "{$brandInitials}-{$marketerInitials}-{$randomSuffix}";
            } while (static::where('tracking_code', $candidate)->where('id', '!=', $this->id)->exists());

            $this->tracking_code = $candidate;
        }

        if (empty($this->tracking_link)) {
            $codeParam = Str::lower($this->tracking_code);
            $this->tracking_link = url("/go/{$codeParam}?ref={$codeParam}");
        }
    }
}
