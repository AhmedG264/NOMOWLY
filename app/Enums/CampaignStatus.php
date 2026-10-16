<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case PAUSED = 'PAUSED';
    case COMPLETED = 'COMPLETED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::ACTIVE => 'نشطة ومتاحة',
            self::PAUSED => 'متوقفة مؤقتاً',
            self::COMPLETED => 'مكتملة',
            self::ARCHIVED => 'مؤرشفة',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-100 text-slate-700 border-slate-200',
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PAUSED => 'bg-amber-50 text-amber-700 border-amber-200',
            self::COMPLETED => 'bg-blue-50 text-blue-700 border-blue-200',
            self::ARCHIVED => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
