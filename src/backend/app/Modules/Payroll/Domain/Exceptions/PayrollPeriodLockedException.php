<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class PayrollPeriodLockedException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Payroll period is locked and cannot be modified.');
    }
}
