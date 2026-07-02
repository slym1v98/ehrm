<?php

namespace App\Modules\Attendance\Application\CommandHandlers\AttendanceAdjustment;

use App\Modules\Attendance\Application\Commands\AttendanceAdjustment\ApproveAttendanceAdjustmentCommand;
use App\Modules\Attendance\Domain\Repositories\AttendanceAdjustmentRequestRepositoryInterface;
use App\Modules\Attendance\Domain\Repositories\AttendanceRawLogRepositoryInterface;
use App\Modules\Attendance\Domain\Repositories\AttendanceTimesheetRepositoryInterface;
use App\Modules\Attendance\Domain\Services\AttendanceCalculator;
use App\Modules\Attendance\Domain\ValueObjects\EventType;
use Carbon\CarbonImmutable;

class ApproveAttendanceAdjustmentHandler
{
    public function __construct(
        private AttendanceAdjustmentRequestRepositoryInterface $adjustmentRepo,
        private AttendanceTimesheetRepositoryInterface $timesheetRepo,
        private AttendanceRawLogRepositoryInterface $rawLogRepo,
    ) {}

    public function handle(ApproveAttendanceAdjustmentCommand $command): void
    {
        $adjustment = $this->adjustmentRepo->findById($command->adjustmentId);
        $adjustment->approve($command->approverId, CarbonImmutable::now());
        $this->adjustmentRepo->saveAndDispatch($adjustment);

        $timesheet = $this->timesheetRepo->findById($adjustment->attendanceTimesheetId());
        if ($timesheet === null) {
            return;
        }

        $logModels = $this->rawLogRepo->findByEmployeeAndRange(
            $timesheet->employeeId(),
            $timesheet->workDate()->format('Y-m-d'),
            $timesheet->workDate()->format('Y-m-d'),
        );

        $rawLogs = array_map(fn ($m) => (object) [
            'eventTime' => CarbonImmutable::instance($m->event_time),
            'eventType' => EventType::from($m->event_type),
        ], $logModels);

        $data = AttendanceCalculator::calculate(
            employeeId: $timesheet->employeeId(),
            workDate: $timesheet->workDate(),
            rawLogs: $rawLogs,
            assignment: null,
            leaves: [],
            holidays: [],
        );

        $timesheet->replaceWith($data, 'adj-' . $adjustment->id()->toString());
        $this->timesheetRepo->saveAndDispatch($timesheet);
    }
}
