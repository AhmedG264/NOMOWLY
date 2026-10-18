<?php

namespace App\Enums;

enum WalletType: string
{
    case BRAND = 'BRAND';
    case MARKETER = 'MARKETER';
    case PLATFORM_INTERNAL = 'PLATFORM_INTERNAL';

    public function label(): string
    {
        return match ($this) {
            self::BRAND => 'محفظة علامة تجارية (Brand)',
            self::MARKETER => 'محفظة مسوق / مبدع (Marketer)',
            self::PLATFORM_INTERNAL => 'محفظة وسيطة للمنصة (Platform)',
        };
    }
}
