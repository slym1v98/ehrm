<?php

namespace App\Modules\Attendance\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class AttendancePeriodClosedException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('ATTENDANCE_PERIOD_CLOSED', trim('Attendance period is closed: '.$detail));
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
