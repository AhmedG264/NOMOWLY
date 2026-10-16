<?php

namespace App\Enums;

enum CampaignApplicationStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case TERMINATED = 'TERMINATED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد المراجعة',
            self::APPROVED => 'معتمدة ونشطة',
            self::REJECTED => 'مرفوضة',
            self::TERMINATED => 'ملغاة',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::APPROVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::TERMINATED => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
