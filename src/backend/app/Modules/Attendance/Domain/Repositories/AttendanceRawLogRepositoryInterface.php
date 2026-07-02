<?php

namespace App\Modules\Attendance\Domain\Repositories;

use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLog;
use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLogId;

interface AttendanceRawLogRepositoryInterface
{
    public function saveAndDispatch(AttendanceRawLog $rawLog): void;
    public function findPaginated(int $perPage = 15, int $page = 1): array;
    public function findByEmployeeAndRange(string $employeeId, string $from, string $to): array;
}
