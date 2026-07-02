<?php

namespace App\Modules\Attendance\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class AttendanceRawLogNotFoundException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('ATTENDANCE_RAW_LOG_NOT_FOUND', trim('Attendance raw log not found: '.$detail));
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}
