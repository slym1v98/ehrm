<?php

namespace App\Modules\Payroll\Domain\Services;

use App\Modules\Payroll\Domain\Aggregates\PayrollComponent\PayrollComponent;
use App\Modules\Payroll\Domain\ValueObjects\Money;
use App\Modules\Payroll\Domain\ValueObjects\PayrollFormulaResult;
use App\Modules\Payroll\Domain\ValueObjects\CalculationType;
use App\Modules\Payroll\Domain\ValueObjects\ComponentCategory;

class PayrollFormulaEngine
{
    private const CATEGORY_ORDER = [
        'base', 'allowance', 'bonus', 'overtime', 'penalty',
        'deduction', 'insurance', 'tax', 'net',
    ];

    private const GROSS_CATEGORIES = ['base', 'allowance', 'bonus', 'overtime'];
    private const NEGATIVE_GROSS = ['penalty'];
    private const DEDUCTION_CATEGORIES = ['deduction', 'insurance', 'tax'];

    /**
     * @param PayrollComponent[] $components
     * @param float $baseSalary Base salary from contract snapshot (in decimal)
     * @param array $manualAmounts Optional map of component code => decimal amount (for manual_entry / overrides)
     */
    public function calculate(
        array $components,
        float $baseSalary,
        array $attendanceData = [],
        array $leaveData = [],
        array $manualAmounts = [],
    ): PayrollFormulaResult {
        // Resolve component amounts by code
        $resolved = [];
        $lines = [];

        // Sort components by category order
        usort($components, function (PayrollComponent $a, PayrollComponent $b) {
            $ai = array_search($a->getCategory()->value, self::CATEGORY_ORDER, true);
            $bi = array_search($b->getCategory()->value, self::CATEGORY_ORDER, true);
            return $ai <=> $bi;
        });

        foreach ($components as $c) {
            if (!$c->isActive()) continue;
            $cat = $c->getCategory()->value;
            if ($cat === 'net') continue; // net is computed, not summed

            $amount = $this->resolveComponentAmount($c, $baseSalary, $resolved, $manualAmounts);
            $resolved[$c->getCode()] = $amount;
            $lines[] = [
                'component_id' => $c->getId()->value,
                'category' => $cat,
                'amount' => $amount,
                'note' => $this->buildNote($c, $amount, $resolved),
            ];
        }

        // Sum gross
        $gross = Money::zero();
        foreach ($lines as $line) {
            if (in_array($line['category'], self::GROSS_CATEGORIES, true)) {
                $gross = $gross->add($line['amount']);
            } elseif (in_array($line['category'], self::NEGATIVE_GROSS, true)) {
                $gross = $gross->subtract($line['amount']);
            }
        }

        // Sum deductions
        $deductions = Money::zero();
        foreach ($lines as $line) {
            if (in_array($line['category'], self::DEDUCTION_CATEGORIES, true)) {
                $deductions = $deductions->add($line['amount']);
            }
        }

        $net = $gross->subtract($deductions);

        return new PayrollFormulaResult($gross, $deductions, $net, $lines);
    }

    private function resolveComponentAmount(
        PayrollComponent $component,
        float $baseSalary,
        array $resolved,
        array $manualAmounts,
    ): Money {
        // Manual amount override always wins if provided
        if (isset($manualAmounts[$component->getCode()])) {
            return Money::fromDecimal($manualAmounts[$component->getCode()]);
        }

        return match ($component->getCalculationType()) {
            CalculationType::FixedAmount => $component->getDefaultAmount() ?? Money::zero(),
            CalculationType::PercentOfComponent => $this->calculatePercent($component, $baseSalary, $resolved),
            CalculationType::ManualEntry => Money::zero(),
        };
    }

    private function calculatePercent(
        PayrollComponent $component,
        float $baseSalary,
        array $resolved,
    ): Money {
        $percent = $component->getDefaultPercent() ?? 0.0;
        // If base component is base_salary (from contract), use baseSalary
        $baseComponentId = $component->getPercentBaseComponentId();
        $baseAmount = null;

        // Find the base component code in resolved via its ID
        foreach ($resolved as $code => $amount) {
            // We index by code, but base is by ID; treat 'base_salary' code as authoritative
            if ($code === 'base_salary') {
                $baseAmount = $amount;
                break;
            }
        }

        if ($baseAmount === null) {
            $baseAmount = Money::fromDecimal($baseSalary);
        }

        $amt = $baseAmount->toDecimal() * $percent / 100;
        return Money::fromDecimal($amt);
    }

    private function buildNote(PayrollComponent $component, Money $amount, array $resolved): ?string
    {
        if ($component->getCalculationType() === CalculationType::PercentOfComponent) {
            return $component->getDefaultPercent().'% of base';
        }
        return null;
    }
}
