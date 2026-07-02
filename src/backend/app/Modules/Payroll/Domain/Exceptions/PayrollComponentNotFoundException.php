<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class PayrollComponentNotFoundException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Payroll component not found.');
    }
}
