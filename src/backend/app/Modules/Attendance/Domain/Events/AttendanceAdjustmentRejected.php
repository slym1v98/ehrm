<?php

namespace App\Modules\Attendance\Domain\Events;

use App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest\AttendanceAdjustmentRequestId;
use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheetId;

final readonly class AttendanceAdjustmentRejected
{
    public function __construct(
        public AttendanceAdjustmentRequestId $requestId,
        public AttendanceTimesheetId $timesheetId,
        public string $approvedBy,
    ) {}
}
