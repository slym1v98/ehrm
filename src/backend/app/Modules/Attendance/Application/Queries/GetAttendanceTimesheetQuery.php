<?php

namespace App\Modules\Attendance\Application\Queries;

final readonly class GetAttendanceTimesheetQuery
{
    public function __construct(
        public string $employeeId,
        public string $from,
        public string $to,
    ) {}
}
