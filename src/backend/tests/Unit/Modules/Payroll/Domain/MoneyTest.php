<?php

namespace Tests\Unit\Modules\Payroll\Domain;

use App\Modules\Payroll\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_add(): void
    {
        $r = Money::fromDecimal(100)->add(Money::fromDecimal(50));
        $this->assertEquals(150, $r->toDecimal());
    }

    public function test_subtract_negative(): void
    {
        $r = Money::fromDecimal(50)->subtract(Money::fromDecimal(100));
        $this->assertTrue($r->isNegative());
    }

    public function test_equals(): void
    {
        $this->assertTrue(Money::fromDecimal(100)->equals(Money::fromDecimal(100)));
        $this->assertFalse(Money::fromDecimal(100)->equals(Money::fromDecimal(101)));
    }
}
