<?php

namespace App\Modules\Payroll\Domain\Exceptions;

class DuplicatePendingAdjustmentException extends \RuntimeException
{
    public static function default(): self
    {
        return new self('A pending adjustment already exists for this entry.');
    }
}
