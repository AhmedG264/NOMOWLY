<?php

namespace App\Enums;

enum Currency: string
{
    case EGP = 'EGP';
    case SAR = 'SAR';
    case AED = 'AED';
    case KWD = 'KWD';
    case QAR = 'QAR';
    case BHD = 'BHD';
    case OMR = 'OMR';
    case USD = 'USD';

    /**
     * Standard decimal scale for the currency.
     * KWD, BHD, and OMR use 3 decimals (1000 fils/baisa).
     * SAR, AED, EGP, QAR, and USD use 2 decimals (100 halalas/piastres/cents).
     */
    public function decimals(): int
    {
        return match ($this) {
            self::KWD, self::BHD, self::OMR => 3,
            default => 2,
        };
    }

    /**
     * Arabic Name for the currency.
     */
    public function label(): string
    {
        return match ($this) {
            self::EGP => 'جنيه مصري (EGP)',
            self::SAR => 'ريال سعودي (SAR)',
            self::AED => 'درهم إماراتي (AED)',
            self::KWD => 'دينار كويتي (KWD)',
            self::QAR => 'ريال قطري (QAR)',
            self::BHD => 'دينار بحريني (BHD)',
            self::OMR => 'ريال عماني (OMR)',
            self::USD => 'دولار أمريكي (USD)',
        };
    }

    /**
     * Short currency symbol in Arabic.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::EGP => 'ج.م',
            self::SAR => 'ر.س',
            self::AED => 'د.إ',
            self::KWD => 'د.ك',
            self::QAR => 'ر.ق',
            self::BHD => 'د.ب',
            self::OMR => 'ر.ع',
            self::USD => '$',
        };
    }

    /**
     * All supported currency codes as an array.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
