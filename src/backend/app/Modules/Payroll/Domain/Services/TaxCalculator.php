<?php

namespace App\Modules\Payroll\Domain\Services;

use App\Modules\Payroll\Domain\ValueObjects\Money;

class TaxCalculator
{
    /**
     * Progressive bracket calculation. Brackets: [['limit'=>float, 'rate'=>float], ...]
     * Default: flat 10% if no brackets provided.
     */
    public function calculate(Money $taxableGross, array $brackets = []): Money
    {
        $decimal = $taxableGross->toDecimal();
        if ($decimal <= 0) return Money::zero();

        if (empty($brackets)) {
            return Money::fromDecimal($decimal * 0.10);
        }

        $tax = 0.0;
        $prev = 0.0;
        foreach ($brackets as $b) {
            $limit = $b['limit'];
            $rate = $b['rate'] / 100;
            if ($decimal > $limit) {
                $tax += ($limit - $prev) * $rate;
                $prev = $limit;
            } else {
                $tax += ($decimal - $prev) * $rate;
                return Money::fromDecimal($tax);
            }
        }
        // If exceeds all brackets, use last bracket's rate for remainder
        $lastRate = end($brackets)['rate'] / 100;
        $tax += ($decimal - $prev) * $lastRate;
        return Money::fromDecimal($tax);
    }
}
