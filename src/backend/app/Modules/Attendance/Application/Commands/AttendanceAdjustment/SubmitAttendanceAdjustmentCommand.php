<?php

namespace App\Modules\Attendance\Application\Commands\AttendanceAdjustment;

final readonly class SubmitAttendanceAdjustmentCommand
{
    public function __construct(
        public string $attendanceTimesheetId,
        public string $employeeId,
        public string $requestedBy,
        public array $corrections,
        public string $reason,
        public ?string $evidenceFile,
    ) {}
}
