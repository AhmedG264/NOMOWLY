<?php

namespace App\Enums;

enum LedgerAccountType: string
{
    case ASSET = 'ASSET';
    case LIABILITY = 'LIABILITY';
    case EQUITY = 'EQUITY';
    case REVENUE = 'REVENUE';
    case EXPENSE = 'EXPENSE';

    /**
     * Normal balance of the account in standard accounting.
     * Assets and Expenses are normally DEBIT.
     * Liabilities, Equities, and Revenues are normally CREDIT.
     */
    public function normalBalance(): LedgerEntryType
    {
        return match ($this) {
            self::ASSET, self::EXPENSE => LedgerEntryType::DEBIT,
            self::LIABILITY, self::EQUITY, self::REVENUE => LedgerEntryType::CREDIT,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ASSET => 'أصول (Assets)',
            self::LIABILITY => 'التزامات (Liabilities)',
            self::EQUITY => 'حقوق ملكية / تسويات (Equity)',
            self::REVENUE => 'إيرادات (Revenue)',
            self::EXPENSE => 'مصروفات واستردادات (Expense)',
        };
    }
}
