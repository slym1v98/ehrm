<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class PayrollPeriodClosedException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Payroll period is closed.');
    }
}
