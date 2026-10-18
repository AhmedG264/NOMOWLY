<?php

namespace App\Enums;

enum BillingCycle: string
{
    case MONTHLY = 'MONTHLY';
    case QUARTERLY = 'QUARTERLY';
    case SEMI_ANNUAL = 'SEMI_ANNUAL';
    case ANNUAL = 'ANNUAL';
    case CUSTOM = 'CUSTOM';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'شهرياً',
            self::QUARTERLY => 'ربع سنوي (3 أشهر)',
            self::SEMI_ANNUAL => 'نصف سنوي (6 أشهر)',
            self::ANNUAL => 'سنوياً (12 شهراً)',
            self::CUSTOM => 'مخصص',
        };
    }

    public function defaultDays(): int
    {
        return match ($this) {
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::SEMI_ANNUAL => 180,
            self::ANNUAL => 365,
            self::CUSTOM => 30,
        };
    }
}
