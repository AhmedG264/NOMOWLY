<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case PENDING_PAYMENT = 'PENDING_PAYMENT';
    case ACTIVE = 'ACTIVE';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
    case SUSPENDED = 'SUSPENDED';
    case PAST_DUE = 'PAST_DUE';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'بانتظار سداد رسوم الاشتراك',
            self::ACTIVE => 'سارٍ ونشط',
            self::EXPIRED => 'منتهٍ الصلاحية',
            self::CANCELLED => 'ملغى من المشترك',
            self::SUSPENDED => 'موقوف إدارياً',
            self::PAST_DUE => 'متأخر السداد',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PENDING_PAYMENT => 'bg-amber-50 text-amber-700 border-amber-200',
            self::EXPIRED => 'bg-slate-100 text-slate-700 border-slate-300',
            self::CANCELLED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::SUSPENDED => 'bg-red-50 text-red-700 border-red-200',
            self::PAST_DUE => 'bg-orange-50 text-orange-700 border-orange-200',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
