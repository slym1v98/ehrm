<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

enum CalculationType: string
{
    case FixedAmount = 'fixed_amount';
    case PercentOfComponent = 'percent_of_component';
    case ManualEntry = 'manual_entry';
}
