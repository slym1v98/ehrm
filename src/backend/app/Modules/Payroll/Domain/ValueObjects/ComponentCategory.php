<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

enum ComponentCategory: string
{
    case Base = 'base';
    case Allowance = 'allowance';
    case Bonus = 'bonus';
    case Penalty = 'penalty';
    case Overtime = 'overtime';
    case Deduction = 'deduction';
    case Insurance = 'insurance';
    case Tax = 'tax';
    case Net = 'net';
}
