<?php

namespace App\Enums;

enum CampaignTermsVersionStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case SUPERSEDED = 'SUPERSEDED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::ACTIVE => 'سارٍ ونشط',
            self::SUPERSEDED => 'مُستبدل بإصدار أحدث',
            self::ARCHIVED => 'مؤرشف',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-100 text-slate-700 border-slate-300',
            self::ACTIVE => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            self::SUPERSEDED => 'bg-amber-50 text-amber-800 border-amber-300',
            self::ARCHIVED => 'bg-rose-50 text-rose-800 border-rose-300',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
