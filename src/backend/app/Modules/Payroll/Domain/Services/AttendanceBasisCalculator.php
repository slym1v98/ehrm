<?php

namespace App\Modules\Payroll\Domain\Services;

use App\Modules\Payroll\Domain\Ports\AttendanceReadPort;
use DateTimeImmutable;

class AttendanceBasisCalculator
{
    public function __construct(private AttendanceReadPort $attendancePort) {}

    /** @return array<string,mixed> Normalized basis for formula input */
    public function getBasis(string $employeeId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $data = $this->attendancePort->getAttendanceForEmployee($employeeId, $start, $end);
        return [
            'worked_minutes' => $data['worked_minutes'] ?? 0,
            'overtime_minutes' => $data['overtime_minutes'] ?? 0,
            'late_minutes' => $data['late_minutes'] ?? 0,
            'paid_leave_minutes' => $data['paid_leave_minutes'] ?? 0,
            'unpaid_leave_minutes' => $data['unpaid_leave_minutes'] ?? 0,
        ];
    }
}
