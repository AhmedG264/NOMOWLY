<?php

namespace App\Models;

use App\Enums\MarketerSpecializationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerSpecialization extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'marketer_profile_id',
        'specialization',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'specialization' => MarketerSpecializationType::class,
            'is_primary' => 'boolean',
        ];
    }

    public function marketerProfile(): BelongsTo
    {
        return $this->belongsTo(MarketerProfile::class);
    }
}
