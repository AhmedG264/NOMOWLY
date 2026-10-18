<?php

namespace App\Enums;

enum PaymentRefundStatus: string
{
    case REQUESTED = 'REQUESTED';
    case PROCESSING = 'PROCESSING';
    case SUCCEEDED = 'SUCCEEDED';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED => 'مطلوب',
            self::PROCESSING => 'جارٍ الاسترداد',
            self::SUCCEEDED => 'مسترد بنجاح',
            self::FAILED => 'فشل الاسترداد',
            self::CANCELLED => 'ملغي',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::REQUESTED => 'bg-amber-50 text-amber-700 border-amber-200',
            self::PROCESSING => 'bg-blue-50 text-blue-700 border-blue-200',
            self::SUCCEEDED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::FAILED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::CANCELLED => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
