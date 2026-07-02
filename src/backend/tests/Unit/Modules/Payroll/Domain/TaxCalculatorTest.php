<?php

namespace Tests\Unit\Modules\Payroll\Domain;

use App\Modules\Payroll\Domain\Services\TaxCalculator;
use App\Modules\Payroll\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class TaxCalculatorTest extends TestCase
{
    public function test_flat_10_percent_default(): void
    {
        $tax = new TaxCalculator();
        $result = $tax->calculate(Money::fromDecimal(10_000_000));
        $this->assertEquals(1_000_000, $result->toDecimal());
    }

    public function test_progressive_brackets(): void
    {
        $tax = new TaxCalculator();
        $brackets = [
            ['limit' => 5_000_000, 'rate' => 5],
            ['limit' => 10_000_000, 'rate' => 10],
        ];
        // 8M taxable: 5M*5% + 3M*10% = 250K + 300K = 550K
        $result = $tax->calculate(Money::fromDecimal(8_000_000), $brackets);
        $this->assertEqualsWithDelta(550_000, $result->toDecimal(), 1);
    }

    public function test_zero_gross(): void
    {
        $tax = new TaxCalculator();
        $this->assertEquals(0, $tax->calculate(Money::zero())->toDecimal());
    }
}
