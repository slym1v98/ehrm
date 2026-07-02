<?php

namespace App\Modules\Payroll\Domain\Ports;

use DateTimeImmutable;

interface AttendanceReadPort
{
    /**
     * @return array{worked_minutes:int, overtime_minutes:int, late_minutes:int, early_leave_minutes:int, paid_leave_minutes:int, unpaid_leave_minutes:int}
     */
    public function getAttendanceForEmployee(string $employeeId, DateTimeImmutable $start, DateTimeImmutable $end): array;
}
