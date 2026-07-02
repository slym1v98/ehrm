<?php

namespace App\Modules\Attendance\Application\Commands\AttendancePeriod;

final readonly class OpenAttendancePeriodCommand
{
    public function __construct(
        public string $periodCode,
        public string $startDate,
        public string $endDate,
    ) {}
}
