<?php

namespace App\Enums;

enum SubscriptionPlanStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'نشطة ومتاحة للاشتراك',
            self::INACTIVE => 'معطلة مؤقتاً',
            self::ARCHIVED => 'مؤرشفة',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::INACTIVE => 'bg-amber-50 text-amber-700 border-amber-200',
            self::ARCHIVED => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
