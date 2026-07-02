<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class InvalidPayrollCalculationException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Invalid payroll calculation.');
    }
}
