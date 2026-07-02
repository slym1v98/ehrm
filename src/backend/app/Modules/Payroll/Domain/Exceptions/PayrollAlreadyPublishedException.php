<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class PayrollAlreadyPublishedException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('Payslips already published for this period.');
    }
}
