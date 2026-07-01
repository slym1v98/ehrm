<?php

namespace App\Modules\Shift\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class ShiftAssignmentNotFoundException extends AppException
{
    public function __construct(string $id = '')
    {
        parent::__construct('SHIFT_ASSIGNMENT_NOT_FOUND', "ShiftAssignment not found: {$id}");
    }
    public function getHttpStatus(): int { return 404; }
}
