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

    public function status(): AdjustmentStatus { return $this->status; }
}
