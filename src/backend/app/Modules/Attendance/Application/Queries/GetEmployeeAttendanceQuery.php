<?php

namespace App\Modules\Attendance\Application\Queries;

final readonly class GetEmployeeAttendanceQuery
{
    public function __construct(public string $employeeId, public string $from, public string $to) {}
}
