<?php

namespace App\Modules\Attendance\Domain\Events;

use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheetId;
use App\Modules\Attendance\Domain\ValueObjects\AttendanceStatus;
use Carbon\CarbonImmutable;

final readonly class AttendanceCalculated
{
    public function __construct(
        public AttendanceTimesheetId $timesheetId,
        public string $employeeId,
        public CarbonImmutable $workDate,
        public AttendanceStatus $resultStatus,
    ) {}
}
