<?php

namespace App\Enums;

enum PaymentDepositStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SUCCEEDED = 'SUCCEEDED';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
    case REFUNDED = 'REFUNDED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::PROCESSING => 'جارٍ المعالجة',
            self::SUCCEEDED => 'مكتمل وناجح',
            self::FAILED => 'فشل الدفع',
            self::CANCELLED => 'ملغي',
            self::PARTIALLY_REFUNDED => 'مسترد جزئياً',
            self::REFUNDED => 'مسترد بالكامل',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::PROCESSING => 'bg-blue-50 text-blue-700 border-blue-200',
            self::SUCCEEDED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::FAILED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::CANCELLED => 'bg-slate-100 text-slate-700 border-slate-300',
            self::PARTIALLY_REFUNDED => 'bg-purple-50 text-purple-700 border-purple-200',
            self::REFUNDED => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        };
    }
}
