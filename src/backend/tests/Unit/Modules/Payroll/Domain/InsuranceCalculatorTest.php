<?php

namespace Tests\Unit\Modules\Payroll\Domain;

use App\Modules\Payroll\Domain\Services\InsuranceCalculator;
use App\Modules\Payroll\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class InsuranceCalculatorTest extends TestCase
{
    public function test_standard_rates(): void
    {
        $ins = new InsuranceCalculator();
        $result = $ins->calculate(Money::fromDecimal(10_000_000));
        $this->assertEquals(800_000, $result['social']->toDecimal()); // 8%
        $this->assertEquals(150_000, $result['health']->toDecimal()); // 1.5%
        $this->assertEquals(100_000, $result['unemployment']->toDecimal()); // 1%
    }
}
