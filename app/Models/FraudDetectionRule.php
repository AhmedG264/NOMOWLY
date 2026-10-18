<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraudDetectionRule extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'rule_key',
        'name_ar',
        'description',
        'is_active',
        'threshold_value',
        'risk_weight',
        'action',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'risk_weight' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
