<?php

namespace App\Modules\Shift\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class OverlappingShiftAssignmentException extends AppException
{
    public function __construct(string $entityId = '')
    {
        parent::__construct('OVERLAPPING_SHIFT_ASSIGNMENT', "Overlapping assignment for entity: {$entityId}");
    }
    public function getHttpStatus(): int { return 422; }
}
