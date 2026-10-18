<?php

namespace App\Enums;

enum CampaignTermsChangeRequestStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد المراجعة الإدارية',
            self::APPROVED => 'معتمد ومُطبق',
            self::REJECTED => 'مرفوض من الإدارة',
            self::CANCELLED => 'مُلغى من العلامة',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-800 border-amber-300',
            self::APPROVED => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            self::REJECTED => 'bg-rose-50 text-rose-800 border-rose-300',
            self::CANCELLED => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
