<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_converts_decimal_strings_to_integer_paise_without_float_drift(): void
    {
        $this->assertSame(10910, Money::toPaise('109.10'));
        $this->assertSame(1, Money::toPaise('0.01'));
        $this->assertSame(0, Money::toPaise(null));
    }

    public function test_formats_with_indian_digit_grouping(): void
    {
        $this->assertSame('₹109', Money::format('109'));
        $this->assertSame('₹1,234', Money::format('1234'));
        $this->assertSame('₹12,34,567', Money::format('1234567'));
        $this->assertSame('₹1,23,456.50', Money::format('123456.5'));
    }

    public function test_from_paise_round_trips(): void
    {
        $this->assertSame('109.50', Money::fromPaise(10950));
        $this->assertSame('-0.05', Money::fromPaise(-5));
    }
}
