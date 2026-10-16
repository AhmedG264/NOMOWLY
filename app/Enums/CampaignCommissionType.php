<?php

namespace App\Enums;

enum CampaignCommissionType: string
{
    case PERCENTAGE_CPO = 'PERCENTAGE_CPO';
    case FIXED_PER_ORDER = 'FIXED_PER_ORDER';
    case CPA_ACTION = 'CPA_ACTION';
    case UGC_FLAT_FEE = 'UGC_FLAT_FEE';
    case HYBRID = 'HYBRID';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE_CPO => 'نسبة من المبيعة (CPO)',
            self::FIXED_PER_ORDER => 'مبلغ ثابت لكل طلب',
            self::CPA_ACTION => 'تكلفة لكل إجراء (CPA)',
            self::UGC_FLAT_FEE => 'مكافأة ثابتة لصناع UGC',
            self::HYBRID => 'نموذج هجين (ثابت + عمولة أداء)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
