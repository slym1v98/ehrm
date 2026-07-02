<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class PayrollRunNotFoundException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Payroll run not found.');
    }
}
