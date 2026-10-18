<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhook extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'payload',
        'signature',
        'status',
        'deposit_id',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(PaymentDeposit::class, 'deposit_id');
    }
}
