<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\Services\Math\MoneyMath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyMathTest extends TestCase
{
    public function test_high_precision_addition(): void
    {
        $sum = MoneyMath::add('100.1234', '50.4321');
        $this->assertEquals('150.5555', $sum);
        $this->assertIsString($sum);
    }

    public function test_high_precision_subtraction(): void
    {
        $diff = MoneyMath::sub('100.5000', '25.2500');
        $this->assertEquals('75.2500', $diff);
        $this->assertIsString($diff);
    }

    public function test_multiplication_without_float_distortion(): void
    {
        // 0.1 * 0.2 in floating point often gives 0.020000000000000004
        $res = MoneyMath::mul('0.1000', '0.2000');
        $this->assertEquals('0.0200', $res);
        $this->assertIsString($res);
    }

    public function test_division_and_zero_division_guard(): void
    {
        $res = MoneyMath::div('100.0000', '4');
        $this->assertEquals('25.0000', $res);

        $this->expectException(InvalidArgumentException::class);
        MoneyMath::div('100.0000', '0');
    }

    public function test_comparison_functions(): void
    {
        $this->assertTrue(MoneyMath::isPositive('0.0001'));
        $this->assertFalse(MoneyMath::isPositive('0.0000'));
        $this->assertTrue(MoneyMath::isZero('0.0000'));
        $this->assertTrue(MoneyMath::isNegative('-0.0001'));
        $this->assertEquals(0, MoneyMath::comp('100.5000', '100.50'));
        $this->assertEquals(1, MoneyMath::comp('100.5001', '100.50'));
        $this->assertEquals(-1, MoneyMath::comp('99.9999', '100.00'));
    }

    public function test_currency_display_formatting(): void
    {
        // KWD uses 3 decimals
        $kwd = MoneyMath::display('1250.7500', Currency::KWD);
        $this->assertEquals('1,250.750', $kwd);

        // SAR uses 2 decimals
        $sar = MoneyMath::display('1250.7500', Currency::SAR);
        $this->assertEquals('1,250.75', $sar);
    }

    public function test_invalid_non_numeric_input_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MoneyMath::format('invalid-string');
    }
}
