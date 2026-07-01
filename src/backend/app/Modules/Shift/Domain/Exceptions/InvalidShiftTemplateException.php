<?php

namespace App\Modules\Shift\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class InvalidShiftTemplateException extends AppException
{
    public function __construct(string $message = '')
    {
        parent::__construct('INVALID_SHIFT_TEMPLATE', $message ?: 'Invalid shift template.');
    }
    public function getHttpStatus(): int { return 422; }
}
