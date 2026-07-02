<?php

namespace App\Modules\Attendance\Application\Commands\AttendancePeriod;

final readonly class CloseAttendancePeriodCommand
{
    public function __construct(public string $periodId) {}
}
