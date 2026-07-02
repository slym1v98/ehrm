<?php

namespace App\Modules\Attendance\Application\Commands\AttendanceTimesheet;

final readonly class CalculateAttendanceForPeriodCommand
{
    public function __construct(
        public string $employeeId,
        public string $from,
        public string $to,
    ) {}
}
