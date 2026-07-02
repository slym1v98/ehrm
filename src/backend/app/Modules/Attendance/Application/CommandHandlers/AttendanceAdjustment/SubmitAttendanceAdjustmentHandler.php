<?php

namespace App\Modules\Attendance\Application\CommandHandlers\AttendanceAdjustment;

use App\Modules\Attendance\Application\Commands\AttendanceAdjustment\SubmitAttendanceAdjustmentCommand;
use App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest\AttendanceAdjustmentRequest;
use App\Modules\Attendance\Domain\Exceptions\AttendancePeriodClosedException;
use App\Modules\Attendance\Domain\Exceptions\AttendanceTimesheetNotFoundException;
use App\Modules\Attendance\Domain\Repositories\AttendanceAdjustmentRequestRepositoryInterface;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;
use App\Modules\Attendance\Domain\Repositories\AttendanceTimesheetRepositoryInterface;

class SubmitAttendanceAdjustmentHandler
{
    public function __construct(
        private AttendanceAdjustmentRequestRepositoryInterface $adjustmentRepo,
        private AttendanceTimesheetRepositoryInterface $timesheetRepo,
        private AttendancePeriodRepositoryInterface $periodRepo,
    ) {}

    public function handle(SubmitAttendanceAdjustmentCommand $command): AttendanceAdjustmentRequest
    {
        $timesheet = $this->timesheetRepo->findById($command->attendanceTimesheetId);
        if ($timesheet === null) {
            throw new AttendanceTimesheetNotFoundException($command->attendanceTimesheetId);
        }

        $period = $this->periodRepo->findClosedByDate($timesheet->workDate()->format('Y-m-d'));
        if ($period !== null) {
            throw new AttendancePeriodClosedException("Timesheet date {$timesheet->workDate()->format('Y-m-d')}");
        }

        $request = AttendanceAdjustmentRequest::submit(
            timesheetId: $command->attendanceTimesheetId,
            employeeId: $command->employeeId,
            requestedBy: $command->requestedBy,
            corrections: $command->corrections,
            reason: $command->reason,
            evidenceFile: $command->evidenceFile,
        );

        $this->adjustmentRepo->saveAndDispatch($request);

        return $request;
    }
}
