<?php

namespace App\Modules\Attendance\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class AttendancePeriodNotFoundException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('ATTENDANCE_PERIOD_NOT_FOUND', trim('Attendance period not found: '.$detail));
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}
