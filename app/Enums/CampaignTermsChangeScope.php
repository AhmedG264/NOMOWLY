<?php

namespace App\Enums;

enum CampaignTermsChangeScope: string
{
    case NEW_MARKETERS_ONLY = 'NEW_MARKETERS_ONLY';
    case ALL_MARKETERS_WITH_CONSENT = 'ALL_MARKETERS_WITH_CONSENT';
    case ALL_MARKETERS = 'ALL_MARKETERS';

    public function label(): string
    {
        return match ($this) {
            self::NEW_MARKETERS_ONLY => 'المسوقون الجدد فقط (دون المساس بالمسوقين السابقين)',
            self::ALL_MARKETERS_WITH_CONSENT => 'جميع المسوقين بعد موافقتهم الصريحة',
            self::ALL_MARKETERS => 'جميع المسوقين سريان عام بعد فترة إشعار',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
