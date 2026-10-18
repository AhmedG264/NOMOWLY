<?php

namespace App\Enums;

enum LedgerTransactionType: string
{
    case DEPOSIT = 'DEPOSIT';
    case WITHDRAWAL = 'WITHDRAWAL';
    case ESCROW_HOLD = 'ESCROW_HOLD';
    case ESCROW_RELEASE = 'ESCROW_RELEASE';
    case COMMISSION_EARNING = 'COMMISSION_EARNING';
    case PLATFORM_FEE = 'PLATFORM_FEE';
    case ADJUSTMENT = 'ADJUSTMENT';
    case REVERSAL = 'REVERSAL';
    case SUBSCRIPTION_PAYMENT = 'SUBSCRIPTION_PAYMENT';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'إيداع أموال في المحفظة',
            self::WITHDRAWAL => 'سحب أموال من المحفظة',
            self::ESCROW_HOLD => 'حجز ميزانية حملة (ضمان معلّق)',
            self::ESCROW_RELEASE => 'تحرير ميزانية محجوزة',
            self::COMMISSION_EARNING => 'استحقاق عمولة تسويقية',
            self::PLATFORM_FEE => 'رسوم وعمولات المنصة',
            self::ADJUSTMENT => 'تسوية محاسبية إدارية',
            self::REVERSAL => 'قيد عكسي إلغائي (Storno/Reversal)',
            self::SUBSCRIPTION_PAYMENT => 'سداد رسوم اشتراك باقة مسوق',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DEPOSIT => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::WITHDRAWAL => 'bg-rose-50 text-rose-700 border-rose-200',
            self::ESCROW_HOLD => 'bg-amber-50 text-amber-700 border-amber-200',
            self::ESCROW_RELEASE => 'bg-blue-50 text-blue-700 border-blue-200',
            self::COMMISSION_EARNING => 'bg-teal-50 text-teal-700 border-teal-200',
            self::PLATFORM_FEE => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::SUBSCRIPTION_PAYMENT => 'bg-violet-50 text-violet-700 border-violet-200',
            self::ADJUSTMENT => 'bg-slate-100 text-slate-700 border-slate-300',
            self::REVERSAL => 'bg-purple-50 text-purple-700 border-purple-200',
        };
    }
}
