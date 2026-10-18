<?php

namespace App\Enums;

enum WalletStatus: string
{
    case ACTIVE = 'ACTIVE';
    case FROZEN = 'FROZEN';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'نشطة',
            self::FROZEN => 'مجمّدة',
            self::CLOSED => 'مغلقة',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::FROZEN => 'bg-amber-50 text-amber-700 border-amber-200',
            self::CLOSED => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
