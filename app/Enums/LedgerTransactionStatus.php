<?php

namespace App\Enums;

enum LedgerTransactionStatus: string
{
    case PENDING = 'PENDING';
    case POSTED = 'POSTED';
    case REVERSED = 'REVERSED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد المعالجة',
            self::POSTED => 'مُرحّل نهائياً (Posted)',
            self::REVERSED => 'معكوس بقيد تصحيحي',
            self::REJECTED => 'مرفوض',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::POSTED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::REVERSED => 'bg-purple-50 text-purple-700 border-purple-200',
            self::REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
}
