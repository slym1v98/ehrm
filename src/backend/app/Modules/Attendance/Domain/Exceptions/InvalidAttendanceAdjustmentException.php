<?php

namespace App\Modules\Attendance\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class InvalidAttendanceAdjustmentException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('INVALID_ATTENDANCE_ADJUSTMENT', trim('Invalid attendance adjustment: '.$detail));
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
