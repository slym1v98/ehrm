<?php

namespace Tests\Unit\Modules\Payroll\Domain;

use App\Modules\Payroll\Domain\Aggregates\PayrollComponent\{PayrollComponent, PayrollComponentId};
use App\Modules\Payroll\Domain\Services\PayrollFormulaEngine;
use App\Modules\Payroll\Domain\ValueObjects\{CalculationType, ComponentCategory, Money};
use PHPUnit\Framework\TestCase;

class PayrollFormulaEngineTest extends TestCase
{
    private function comp(string $code, string $category, string $calcType, ?float $amount = null, ?float $percent = null): PayrollComponent
    {
        return PayrollComponent::create(
            PayrollComponentId::generate(),
            $code,
            $code,
            ComponentCategory::from($category),
            CalculationType::from($calcType),
            $calcType === 'percent_of_component' ? 'base-id-1' : null,
            $amount !== null ? Money::fromDecimal($amount) : null,
            $percent,
        );
    }

    public function test_fixed_amount_only_gross(): void
    {
        $base = $this->comp('base_salary', 'base', 'fixed_amount', 5_000_000);
        $meal = $this->comp('meal_allowance', 'allowance', 'fixed_amount', 730_000);
        $engine = new PayrollFormulaEngine();

        $r = $engine->calculate([$base, $meal], 5_000_000);
        $this->assertEquals(5_730_000, $r->gross->toDecimal());
        $this->assertEquals(0, $r->deduction->toDecimal());
        $this->assertEquals(5_730_000, $r->net->toDecimal());
    }

    public function test_percent_of_base_and_deductions(): void
    {
        $base = $this->comp('base_salary', 'base', 'fixed_amount', 5_000_000);
        $social = $this->comp('social_insurance', 'insurance', 'percent_of_component', null, 8);
        $engine = new PayrollFormulaEngine();

        $r = $engine->calculate([$base, $social], 5_000_000);
        $this->assertEquals(5_000_000, $r->gross->toDecimal());
        $this->assertEquals(400_000, $r->deduction->toDecimal()); // 8% of 5M
        $this->assertEquals(4_600_000, $r->net->toDecimal());
    }

    public function test_full_scenario(): void
    {
        $components = [
            $this->comp('base_salary','base','fixed_amount', 5_000_000),
            $this->comp('position_allowance','allowance','percent_of_component', null, 10),
            $this->comp('meal_allowance','allowance','fixed_amount', 730_000),
            $this->comp('social_insurance','insurance','percent_of_component', null, 8),
            $this->comp('health_insurance','insurance','percent_of_component', null, 1.5),
            $this->comp('income_tax','tax','percent_of_component', null, 10),
        ];
        $engine = new PayrollFormulaEngine();
        $r = $engine->calculate($components, 5_000_000);
        // gross = 5M + 500K + 730K = 6.23M
        $this->assertEqualsWithDelta(6_230_000, $r->gross->toDecimal(), 1);
        // deductions = 400K + 75K + 500K = 975K
        $this->assertEqualsWithDelta(975_000, $r->deduction->toDecimal(), 1);
        // net = 5.255M
        $this->assertEqualsWithDelta(5_255_000, $r->net->toDecimal(), 1);
    }

    public function test_penalty_subtracts_from_gross(): void
    {
        $base = $this->comp('base_salary', 'base', 'fixed_amount', 5_000_000);
        $penalty = $this->comp('penalty', 'penalty', 'fixed_amount', 500_000);
        $engine = new PayrollFormulaEngine();
        $r = $engine->calculate([$base, $penalty], 5_000_000);
        $this->assertEquals(4_500_000, $r->gross->toDecimal());
    }
}
