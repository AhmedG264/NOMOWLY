<?php

namespace App\Models;

use App\Enums\CampaignTermsChangeRequestStatus;
use App\Enums\CampaignTermsChangeScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignTermsChangeRequest extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'campaign_id',
        'current_version_id',
        'status',
        'scope',
        'reason',
        'proposed_effective_from',
        'proposed_terms',
        'previous_terms_snapshot',
        'diff_summary',
        'requested_by_user_id',
        'reviewed_by_user_id',
        'reviewer_notes',
        'reviewed_at',
        'applied_terms_version_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignTermsChangeRequestStatus::class,
            'scope' => CampaignTermsChangeScope::class,
            'proposed_effective_from' => 'datetime',
            'proposed_terms' => 'array',
            'previous_terms_snapshot' => 'array',
            'diff_summary' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(CampaignTermsVersion::class, 'current_version_id');
    }

    public function appliedVersion(): BelongsTo
    {
        return $this->belongsTo(CampaignTermsVersion::class, 'applied_terms_version_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === CampaignTermsChangeRequestStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === CampaignTermsChangeRequestStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === CampaignTermsChangeRequestStatus::REJECTED;
    }
}
