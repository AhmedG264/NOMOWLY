<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributionClick extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false; // Only created_at is maintained

    protected $fillable = [
        'campaign_id',
        'campaign_application_id',
        'terms_version_id',
        'tracking_code',
        'tracking_link',
        'visitor_id',
        'session_id',
        'ip_hash',
        'user_agent_hash',
        'referrer',
        'landing_url',
        'country',
        'device_type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
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

    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }
}
