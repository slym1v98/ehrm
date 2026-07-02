<?php

namespace App\Modules\Attendance\Infrastructure\Persistence\Repositories;

use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheet;
use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheetId;
use App\Modules\Attendance\Domain\Repositories\AttendanceTimesheetRepositoryInterface;
use App\Modules\Attendance\Domain\ValueObjects\AttendanceStatus;
use App\Modules\Attendance\Infrastructure\Persistence\Eloquent\AttendanceTimesheetModel;
use Carbon\CarbonImmutable;

class EloquentAttendanceTimesheetRepository implements AttendanceTimesheetRepositoryInterface
{
    public function findById(string $id): ?AttendanceTimesheet
    {
        $model = AttendanceTimesheetModel::find($id);
        return $model ? $this->toAggregate($model) : null;
    }

    public function findByEmployeeDatePeriod(string $employeeId, string $workDate, string $periodId): ?AttendanceTimesheet
    {
        $model = AttendanceTimesheetModel::where('employee_id', $employeeId)
            ->where('work_date', $workDate)
            ->where('attendance_period_id', $periodId)
            ->first();
        return $model ? $this->toAggregate($model) : null;
    }

    public function saveAndDispatch(AttendanceTimesheet $timesheet): void
    {
        AttendanceTimesheetModel::updateOrCreate(
            ['id' => $timesheet->id()->toString()],
            [
                'attendance_period_id' => $timesheet->attendancePeriodId(),
                'employee_id' => $timesheet->employeeId(),
                'work_date' => $timesheet->workDate()->format('Y-m-d'),
                'shift_assignment_id' => $timesheet->shiftAssignmentId(),
                'expected_minutes' => $timesheet->expectedMinutes(),
                'worked_minutes' => $timesheet->workedMinutes(),
                'late_minutes' => $timesheet->lateMinutes(),
                'early_leave_minutes' => $timesheet->earlyLeaveMinutes(),
                'overtime_minutes' => $timesheet->overtimeMinutes(),
                'result_status' => $timesheet->resultStatus()->value,
                'calculation_run_id' => $timesheet->calculationRunId(),
            ]
        );

        foreach ($timesheet->releaseEvents() as $event) {
            event($event);
        }
    }

    public function findPaginated(int $perPage = 15, int $page = 1): array
    {
        return AttendanceTimesheetModel::paginate($perPage)->items();
    }

    public function findByEmployeeAndRange(string $employeeId, string $from, string $to): array
    {
        return AttendanceTimesheetModel::where('employee_id', $employeeId)
            ->whereBetween('work_date', [$from, $to])
            ->get()
            ->all();
    }

    private function toAggregate(AttendanceTimesheetModel $model): AttendanceTimesheet
    {
        return AttendanceTimesheet::reconstitute(
            id: AttendanceTimesheetId::fromString($model->id),
            attendancePeriodId: $model->attendance_period_id,
            employeeId: $model->employee_id,
            workDate: CarbonImmutable::instance($model->work_date),
            shiftAssignmentId: $model->shift_assignment_id,
            expectedMinutes: (int) $model->expected_minutes,
            workedMinutes: (int) $model->worked_minutes,
            lateMinutes: (int) $model->late_minutes,
            earlyLeaveMinutes: (int) $model->early_leave_minutes,
            overtimeMinutes: (int) $model->overtime_minutes,
            resultStatus: AttendanceStatus::from($model->result_status),
            calculationRunId: $model->calculation_run_id,
        );
    }
}
