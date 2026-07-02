<?php

namespace App\Modules\Attendance\Application\Commands\AttendanceAdjustment;

final readonly class RejectAttendanceAdjustmentCommand
{
    public function __construct(
        public string $adjustmentId,
        public string $approverId,
    ) {}
}
