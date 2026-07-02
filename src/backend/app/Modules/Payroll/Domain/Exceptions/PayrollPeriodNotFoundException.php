<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class PayrollPeriodNotFoundException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Payroll period not found.');
    }
}
