<?php

namespace App\Modules\Attendance\Domain\Repositories;

use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheet;
use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheetId;

interface AttendanceTimesheetRepositoryInterface
{
    public function findById(string $id): ?AttendanceTimesheet;
    public function findByEmployeeDatePeriod(string $employeeId, string $workDate, string $periodId): ?AttendanceTimesheet;
    public function saveAndDispatch(AttendanceTimesheet $timesheet): void;
    public function findPaginated(int $perPage = 15, int $page = 1): array;
    public function findByEmployeeAndRange(string $employeeId, string $from, string $to): array;
}
