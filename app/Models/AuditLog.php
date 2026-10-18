<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'action',
        'status',
        'entity_type',
        'entity_id',
        'payload_diff',
        'reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'action' => AuditAction::class,
        'payload_diff' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Enforce strict immutability at the application model layer.
     * Audit logs are append-only; update and delete operations throw an exception.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('سجلات المراقبة والعمليات غير قابلة للتعديل نهائياً (Append-Only Audit Log).');
        });

        static::deleting(function () {
            throw new RuntimeException('سجلات المراقبة والعمليات غير قابلة للحذف نهائياً (Append-Only Audit Log).');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable Arabic label for the log action.
     */
    public function actionLabel(): string
    {
        return $this->action instanceof AuditAction
            ? $this->action->label()
            : (string) $this->action;
    }

    /**
     * Human-readable Arabic label for operation status.
     */
    public function statusLabel(): string
    {
        return match (strtoupper((string) $this->status)) {
            'SUCCESS' => 'ناجحة',
            'FAILED' => 'فاشلة',
            'REJECTED' => 'مرفوضة',
            'APPROVED' => 'معتمدة',
            'CANCELLED' => 'ملغاة',
            'UNAUTHORIZED' => 'محظورة / غير مصرح بها',
            'WARNING' => 'تنبيه أمني',
            default => $this->status ?: 'ناجحة',
        };
    }

    /**
     * Badge CSS classes for status indicator.
     */
    public function statusBadgeClass(): string
    {
        return match (strtoupper((string) $this->status)) {
            'SUCCESS', 'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'FAILED', 'REJECTED', 'UNAUTHORIZED' => 'bg-rose-50 text-rose-700 border-rose-200',
            'CANCELLED' => 'bg-slate-100 text-slate-700 border-slate-300',
            'WARNING' => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}
