<?php

namespace App\Modules\Attendance\Application\Commands\AttendancePeriod;

final readonly class ReopenAttendancePeriodCommand
{
    public function __construct(
        public string $periodId,
        public string $reason,
    ) {}
}
