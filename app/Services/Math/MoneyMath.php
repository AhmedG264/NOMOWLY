<?php

namespace App\Services\Math;

use App\Enums\Currency;
use InvalidArgumentException;

/**
 * High-precision monetary arithmetic service.
 * Enforces strict string-based BCMath operations with ZERO float calculations.
 */
class MoneyMath
{
    /**
     * Standard internal decimal scale for ledger calculations (4 decimals).
     * This fully accommodates 3-decimal currencies (KWD, BHD, OMR) and 2-decimal currencies (SAR, EGP, USD, etc.)
     * without any rounding distortion.
     */
    public const SCALE = 4;

    /**
     * Format/normalize any numeric string or int to a clean scale-4 string.
     */
    public static function format(string|int $amount, int $scale = self::SCALE): string
    {
        $amountStr = trim((string) $amount);

        if (!is_numeric($amountStr)) {
            throw new InvalidArgumentException("القيمة المالية المدخلة غير صالحة: [{$amountStr}]");
        }

        // Use bcadd with 0 to safely truncate/pad to exact scale without floating point casting
        return bcadd($amountStr, '0', $scale);
    }

    /**
     * Add two monetary values: $a + $b
     */
    public static function add(string|int $a, string|int $b, int $scale = self::SCALE): string
    {
        return bcadd(self::format($a, $scale), self::format($b, $scale), $scale);
    }

    /**
     * Subtract two monetary values: $a - $b
     */
    public static function sub(string|int $a, string|int $b, int $scale = self::SCALE): string
    {
        return bcsub(self::format($a, $scale), self::format($b, $scale), $scale);
    }

    /**
     * Multiply monetary value by factor: $a * $factor
     */
    public static function mul(string|int $a, string|int $factor, int $scale = self::SCALE): string
    {
        return bcmul(self::format($a, $scale), (string) $factor, $scale);
    }

    /**
     * Divide monetary value by divisor: $a / $divisor
     */
    public static function div(string|int $a, string|int $divisor, int $scale = self::SCALE): string
    {
        $normDivisor = self::format($divisor, $scale);
        if (self::isZero($normDivisor, $scale)) {
            throw new InvalidArgumentException("لا يمكن القسمة على صفر في العمليات المالية.");
        }

        return bcdiv(self::format($a, $scale), $normDivisor, $scale);
    }

    /**
     * Compare two values:
     * Returns 0 if equal, 1 if $a > $b, -1 if $a < $b
     */
    public static function comp(string|int $a, string|int $b, int $scale = self::SCALE): int
    {
        return bccomp(self::format($a, $scale), self::format($b, $scale), $scale);
    }

    /**
     * Check if amount is strictly greater than zero.
     */
    public static function isPositive(string|int $amount, int $scale = self::SCALE): bool
    {
        return self::comp($amount, '0', $scale) > 0;
    }

    /**
     * Check if amount is exactly zero.
     */
    public static function isZero(string|int $amount, int $scale = self::SCALE): bool
    {
        return self::comp($amount, '0', $scale) === 0;
    }

    /**
     * Check if amount is less than zero.
     */
    public static function isNegative(string|int $amount, int $scale = self::SCALE): bool
    {
        return self::comp($amount, '0', $scale) < 0;
    }

    /**
     * Format for display according to currency decimals.
     * e.g. KWD => 3 decimals, SAR => 2 decimals.
     */
    public static function display(string|int $amount, string|Currency $currency): string
    {
        $currEnum = $currency instanceof Currency ? $currency : Currency::from(strtoupper(trim($currency)));
        $decimals = $currEnum->decimals();
        $normalized = self::format($amount, $decimals);

        // Format with thousands separator for human readability
        $parts = explode('.', $normalized);
        $integerPart = number_format((int) $parts[0]);
        $fractionPart = $parts[1] ?? str_repeat('0', $decimals);

        return $integerPart . '.' . $fractionPart;
    }
}
