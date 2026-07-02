<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class PayrollNotApprovedException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Payroll must be approved before locking.');
    }
}
