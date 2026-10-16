<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'نشط',
            self::SUSPENDED => 'موقوف',
            self::PENDING_VERIFICATION => 'قيد التحقق',
        };
    }
}
