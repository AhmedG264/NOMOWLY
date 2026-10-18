<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case DEBIT = 'DEBIT';
    case CREDIT = 'CREDIT';

    public function label(): string
    {
        return match ($this) {
            self::DEBIT => 'مدين (Debit)',
            self::CREDIT => 'دائن (Credit)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DEBIT => 'bg-blue-50 text-blue-700 border-blue-200',
            self::CREDIT => 'bg-purple-50 text-purple-700 border-purple-200',
        };
    }
}
