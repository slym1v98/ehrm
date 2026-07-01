<?php

namespace App\Modules\Shift\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class ShiftTemplateNotFoundException extends AppException
{
    public function __construct(string $id = '')
    {
        parent::__construct('SHIFT_TEMPLATE_NOT_FOUND', "ShiftTemplate not found: {$id}");
    }
    public function getHttpStatus(): int { return 404; }
}
