<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class DuplicatePayrollRunException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('An active run already exists for this period.');
    }
}
