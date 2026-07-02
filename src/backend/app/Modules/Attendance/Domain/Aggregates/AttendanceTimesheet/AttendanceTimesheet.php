<?php

namespace App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet;

use App\Modules\Attendance\Domain\Events\AttendanceCalculated;
use App\Modules\Attendance\Domain\ValueObjects\AttendanceStatus;
use App\Modules\Attendance\Domain\ValueObjects\TimesheetData;
use Carbon\CarbonImmutable;

class AttendanceTimesheet
{
    private array $events = [];

    private function __construct(
        private AttendanceTimesheetId $id,
        private string $attendancePeriodId,
        private string $employeeId,
        private CarbonImmutable $workDate,
        private ?string $shiftAssignmentId,
        private int $expectedMinutes,
        private int $workedMinutes,
        private int $lateMinutes,
        private int $earlyLeaveMinutes,
        private int $overtimeMinutes,
        private AttendanceStatus $resultStatus,
        private ?string $calculationRunId,
    ) {}

    public static function fromCalculation(
        string $periodId,
        string $employeeId,
        CarbonImmutable $workDate,
        ?string $shiftAssignmentId,
        TimesheetData $data,
    ): self {
        $id = AttendanceTimesheetId::generate();
        $instance = new self(
            $id, $periodId, $employeeId, $workDate,
            $shiftAssignmentId,
            $data->expectedMinutes, $data->workedMinutes,
            $data->lateMinutes, $data->earlyLeaveMinutes,
            $data->overtimeMinutes, $data->status, null,
        );
        $instance->events[] = new AttendanceCalculated(
            timesheetId: $id,
            employeeId: $employeeId,
            workDate: $workDate,
            resultStatus: $data->status,
        );

        return $instance;
    }

    public static function reconstitute(
        AttendanceTimesheetId $id,
        string $attendancePeriodId,
        string $employeeId,
        CarbonImmutable $workDate,
        ?string $shiftAssignmentId,
        int $expectedMinutes,
        int $workedMinutes,
        int $lateMinutes,
        int $earlyLeaveMinutes,
        int $overtimeMinutes,
        AttendanceStatus $resultStatus,
        ?string $calculationRunId,
    ): self {
        return new self(
            $id, $attendancePeriodId, $employeeId, $workDate,
            $shiftAssignmentId, $expectedMinutes, $workedMinutes,
            $lateMinutes, $earlyLeaveMinutes, $overtimeMinutes,
            $resultStatus, $calculationRunId,
        );
    }

    public function replaceWith(TimesheetData $data, string $calculationRunId): void
    {
        $this->expectedMinutes = $data->expectedMinutes;
        $this->workedMinutes = $data->workedMinutes;
        $this->lateMinutes = $data->lateMinutes;
        $this->earlyLeaveMinutes = $data->earlyLeaveMinutes;
        $this->overtimeMinutes = $data->overtimeMinutes;
        $this->resultStatus = $data->status;
        $this->calculationRunId = $calculationRunId;

        $this->events[] = new AttendanceCalculated(
            timesheetId: $this->id,
            employeeId: $this->employeeId,
            workDate: $this->workDate,
            resultStatus: $data->status,
        );
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    public function id(): AttendanceTimesheetId { return $this->id; }
    public function attendancePeriodId(): string { return $this->attendancePeriodId; }
    public function employeeId(): string { return $this->employeeId; }
    public function workDate(): CarbonImmutable { return $this->workDate; }
    public function shiftAssignmentId(): ?string { return $this->shiftAssignmentId; }
    public function expectedMinutes(): int { return $this->expectedMinutes; }
    public function workedMinutes(): int { return $this->workedMinutes; }
    public function lateMinutes(): int { return $this->lateMinutes; }
    public function earlyLeaveMinutes(): int { return $this->earlyLeaveMinutes; }
    public function overtimeMinutes(): int { return $this->overtimeMinutes; }
    public function resultStatus(): AttendanceStatus { return $this->resultStatus; }
    public function calculationRunId(): ?string { return $this->calculationRunId; }
}
