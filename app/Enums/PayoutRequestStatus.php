<?php

namespace App\Enums;

enum PayoutRequestStatus: string
{
    case REQUESTED = 'REQUESTED';
    case APPROVED = 'APPROVED';
    case PROCESSING = 'PROCESSING';
    case PAID = 'PAID';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED => 'قيد المراجعة الإدارية',
            self::APPROVED => 'معتمد وبانتظار الصرف',
            self::PROCESSING => 'قيد التنفيذ المالي',
            self::PAID => 'تم الصرف بنجاح',
            self::REJECTED => 'مرفوض',
            self::CANCELLED => 'ملغي',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::REQUESTED => 'bg-amber-50 text-amber-700 border-amber-200',
            self::APPROVED => 'bg-blue-50 text-blue-700 border-blue-200',
            self::PROCESSING => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::PAID => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::CANCELLED => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
