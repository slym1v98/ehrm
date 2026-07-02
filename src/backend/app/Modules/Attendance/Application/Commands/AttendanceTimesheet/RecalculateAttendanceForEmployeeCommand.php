<?php

namespace App\Modules\Attendance\Application\Commands\AttendanceTimesheet;

final readonly class RecalculateAttendanceForEmployeeCommand
{
    public function __construct(
        public string $employeeId,
        public string $timesheetId,
    ) {}
}
