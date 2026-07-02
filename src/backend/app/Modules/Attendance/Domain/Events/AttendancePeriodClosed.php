<?php

namespace App\Modules\Attendance\Domain\Events;

use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriodId;

final readonly class AttendancePeriodClosed
{
    public function __construct(
        public AttendancePeriodId $periodId,
        public string $periodCode,
    ) {}
}
