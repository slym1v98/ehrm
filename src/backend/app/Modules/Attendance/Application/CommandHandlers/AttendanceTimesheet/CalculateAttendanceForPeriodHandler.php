<?php

namespace App\Modules\Attendance\Application\CommandHandlers\AttendanceTimesheet;

use App\Modules\Attendance\Application\Commands\AttendanceTimesheet\CalculateAttendanceForPeriodCommand;
use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheet;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;
use App\Modules\Attendance\Domain\Repositories\AttendanceRawLogRepositoryInterface;
use App\Modules\Attendance\Domain\Repositories\AttendanceTimesheetRepositoryInterface;
use App\Modules\Attendance\Domain\Services\AttendanceCalculator;
use Carbon\CarbonImmutable;

class CalculateAttendanceForPeriodHandler
{
    public function __construct(
        private AttendanceTimesheetRepositoryInterface $timesheetRepo,
        private AttendanceRawLogRepositoryInterface $rawLogRepo,
        private AttendancePeriodRepositoryInterface $periodRepo,
    ) {}

    // ponytail: Leave/holiday real read-model wiring deferred until those modules expose stable query contracts.
    public function handle(CalculateAttendanceForPeriodCommand $command): void
    {
        $from = CarbonImmutable::parse($command->from);
        $to = CarbonImmutable::parse($command->to);
        $rawLogs = $this->rawLogRepo->findByEmployeeAndRange($command->employeeId, $from->toIso8601String(), $to->toIso8601String());

        $period = $this->periodRepo->findByCode($from->format('Y-m'));
        if (! $period) {
            return;
        }

        for ($workDate = $from; $workDate->lessThanOrEqualTo($to); $workDate = $workDate->addDay()) {
            $data = AttendanceCalculator::calculate(
                employeeId: $command->employeeId,
                workDate: $workDate,
                rawLogs: $this->filterLogsByDate($rawLogs, $workDate),
                assignment: null,
                leaves: [],
                holidays: [],
            );

            $timesheet = AttendanceTimesheet::fromCalculation(
                periodId: $period->id()->toString(),
                employeeId: $command->employeeId,
                workDate: $workDate,
                shiftAssignmentId: null,
                data: $data,
            );

            $this->timesheetRepo->saveAndDispatch($timesheet);
        }
    }

    private function filterLogsByDate(array $rawLogs, CarbonImmutable $date): array
    {
        return array_values(array_filter($rawLogs, function ($log) use ($date) {
            $time = property_exists($log, 'eventTime') ? $log->eventTime : ($log->event_time ?? null);
            return $time && CarbonImmutable::instance($time)->isSameDay($date);
        }));
    }
}
