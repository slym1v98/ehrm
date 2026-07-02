<?php

namespace App\Modules\Payroll\Domain\Services;

use App\Modules\Payroll\Domain\ValueObjects\Money;

class InsuranceCalculator
{
    private const SOCIAL_RATE = 0.08;
    private const HEALTH_RATE = 0.015;
    private const UNEMPLOYMENT_RATE = 0.01;

    /** @return array{social:Money, health:Money, unemployment:Money} */
    public function calculate(Money $baseSalary): array
    {
        $decimal = $baseSalary->toDecimal();
        return [
            'social' => Money::fromDecimal($decimal * self::SOCIAL_RATE),
            'health' => Money::fromDecimal($decimal * self::HEALTH_RATE),
            'unemployment' => Money::fromDecimal($decimal * self::UNEMPLOYMENT_RATE),
        ];
    }
}
