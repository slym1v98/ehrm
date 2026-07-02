<?php

namespace App\Modules\Attendance\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class InvalidAttendanceCalculationException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('INVALID_ATTENDANCE_CALCULATION', trim('Invalid attendance calculation: '.$detail));
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
