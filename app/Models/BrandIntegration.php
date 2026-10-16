<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BrandIntegration extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'brand_profile_id',
        'name',
        'provider',
        'api_key',
        'api_secret',
        'status',
        'webhook_url',
        'allowed_ips',
        'last_used_at',
        'metadata',
    ];

    /**
     * Hidden attributes to ensure secrets are never serialized to JSON or API responses
     */
    protected $hidden = [
        'api_secret',
    ];

    protected function casts(): array
    {
        return [
            'api_secret' => 'encrypted',
            'allowed_ips' => 'array',
            'metadata' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfile::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Generate secure, high-entropy API key and Secret
     *
     * @return array{api_key: string, api_secret: string}
     */
    public static function generateCredentials(): array
    {
        return [
            'api_key' => 'nmw_live_' . bin2hex(random_bytes(24)),
            'api_secret' => 'sec_live_' . bin2hex(random_bytes(32)),
        ];
    }

    /**
     * Timing-safe verification of HMAC signature
     */
    public function verifySignature(string $payload, string $timestamp, string $providedSignature): bool
    {
        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $this->api_secret);
        return hash_equals($expectedSignature, strtolower(trim($providedSignature)));
    }
}
