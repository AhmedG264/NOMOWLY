<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case BRAND = 'BRAND';
    case MARKETER = 'MARKETER';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'المدير العام (Super Admin)',
            self::BRAND => 'العلامة التجارية (Brand)',
            self::MARKETER => 'المسوق بالعمولة (Marketer)',
        };
    }

    /**
     * Public registration allows only BRAND and MARKETER.
     */
    public static function publicRegisterable(): array
    {
        return [
            self::BRAND->value,
            self::MARKETER->value,
        ];
    }
}
