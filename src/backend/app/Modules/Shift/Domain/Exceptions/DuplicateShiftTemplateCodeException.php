<?php

namespace App\Modules\Shift\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class DuplicateShiftTemplateCodeException extends AppException
{
    public function __construct(string $code = '')
    {
        parent::__construct('DUPLICATE_SHIFT_TEMPLATE_CODE', "Shift template code already exists: {$code}");
    }
    public function getHttpStatus(): int { return 409; }
}
