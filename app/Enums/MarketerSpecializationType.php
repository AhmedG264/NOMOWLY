<?php

namespace App\Enums;

enum MarketerSpecializationType: string
{
    case CREATOR = 'CREATOR';
    case AFFILIATE = 'AFFILIATE';
    case MEDIA_BUYER = 'MEDIA_BUYER';

    public function label(): string
    {
        return match ($this) {
            self::CREATOR => 'صانع محتوى ومؤثر (Creator)',
            self::AFFILIATE => 'مسوق بالعمولة (Affiliate)',
            self::MEDIA_BUYER => 'مشتري إعلانات (Media Buyer)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
