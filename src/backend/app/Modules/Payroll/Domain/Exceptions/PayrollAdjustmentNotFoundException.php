<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class PayrollAdjustmentNotFoundException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Payroll adjustment not found.');
    }
}
