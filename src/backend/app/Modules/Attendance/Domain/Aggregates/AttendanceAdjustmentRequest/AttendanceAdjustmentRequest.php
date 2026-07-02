<?php

namespace App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest;

use App\Modules\Attendance\Domain\Events\AttendanceAdjustmentApproved;
use App\Modules\Attendance\Domain\Events\AttendanceAdjustmentRejected;
use App\Modules\Attendance\Domain\Events\AttendanceAdjustmentRequested;
use App\Modules\Attendance\Domain\Exceptions\InvalidAttendanceAdjustmentException;
use App\Modules\Attendance\Domain\ValueObjects\AdjustmentStatus;
use Carbon\CarbonImmutable;

class AttendanceAdjustmentRequest
{
    private array $events = [];

    private function __construct(
        private AttendanceAdjustmentRequestId $id,
        private string $attendanceTimesheetId,
        private string $employeeId,
        private string $requestedBy,
        private string $reason,
        private ?string $evidenceFile,
        private array $corrections,
        private AdjustmentStatus $status,
        private ?string $approvedBy,
        private ?CarbonImmutable $approvedAt,
    ) {}

    public static function submit(
        string $timesheetId,
        string $employeeId,
        string $requestedBy,
        array $corrections,
        string $reason,
        ?string $evidenceFile,
    ): self {
        $id = AttendanceAdjustmentRequestId::generate();
        $instance = new self(
            $id, $timesheetId, $employeeId, $requestedBy,
            $reason, $evidenceFile, $corrections,
            AdjustmentStatus::Pending, null, null,
        );
        $instance->events[] = new AttendanceAdjustmentRequested(
            requestId: $id,
            timesheetId: $timesheetId,
            employeeId: $employeeId,
        );

        return $instance;
    }

    public static function reconstitute(
        AttendanceAdjustmentRequestId $id,
        string $attendanceTimesheetId,
        string $employeeId,
        string $requestedBy,
        string $reason,
        ?string $evidenceFile,
        array $corrections,
        AdjustmentStatus $status,
        ?string $approvedBy,
        ?CarbonImmutable $approvedAt,
    ): self {
        return new self(
            $id, $attendanceTimesheetId, $employeeId, $requestedBy,
            $reason, $evidenceFile, $corrections, $status,
            $approvedBy, $approvedAt,
        );
    }

    public function approve(string $approverId, CarbonImmutable $at): void
    {
        if ($this->status !== AdjustmentStatus::Pending) {
            throw new InvalidAttendanceAdjustmentException('Only pending requests can be approved');
        }

        $this->status = AdjustmentStatus::Approved;
        $this->approvedBy = $approverId;
        $this->approvedAt = $at->microsecond(0);

        $this->events[] = new AttendanceAdjustmentApproved(
            requestId: $this->id,
            timesheetId: $this->attendanceTimesheetId,
            approvedBy: $approverId,
        );
    }

    public function reject(string $approverId, CarbonImmutable $at): void
    {
        if ($this->status !== AdjustmentStatus::Pending) {
            throw new InvalidAttendanceAdjustmentException('Only pending requests can be rejected');
        }

        $this->status = AdjustmentStatus::Rejected;
        $this->approvedBy = $approverId;
        $this->approvedAt = $at->microsecond(0);

        $this->events[] = new AttendanceAdjustmentRejected(
            requestId: $this->id,
            timesheetId: $this->attendanceTimesheetId,
            approvedBy: $approverId,
        );
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    public function id(): AttendanceAdjustmentRequestId { return $this->id; }
    public function attendanceTimesheetId(): string { return $this->attendanceTimesheetId; }
    public function employeeId(): string { return $this->employeeId; }
    public function requestedBy(): string { return $this->requestedBy; }
    public function reason(): string { return $this->reason; }
    public function evidenceFile(): ?string { return $this->evidenceFile; }
    public function corrections(): array { return $this->corrections; }
    public function status(): AdjustmentStatus { return $this->status; }
    public function approvedBy(): ?string { return $this->approvedBy; }
    public function approvedAt(): ?CarbonImmutable { return $this->approvedAt; }
}
