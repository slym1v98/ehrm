<?php

namespace App\Modules\Attendance\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class AttendanceTimesheetNotFoundException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('ATTENDANCE_TIMESHEET_NOT_FOUND', trim('Attendance timesheet not found: '.$detail));
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}
