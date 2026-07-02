<?php

namespace App\Modules\Attendance\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class DuplicatePendingAdjustmentException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('DUPLICATE_PENDING_ATTENDANCE_ADJUSTMENT', trim('Pending adjustment already exists: '.$detail));
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}
