<?php

namespace App\Modules\Leave\Domain\Aggregates\LeaveRequest;

use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Events\LeaveRequestApproved;
use App\Modules\Leave\Domain\Events\LeaveRequestCancelled;
use App\Modules\Leave\Domain\Events\LeaveRequestRejected;
use App\Modules\Leave\Domain\Events\LeaveRequestSubmitted;
use App\Modules\Leave\Domain\Exceptions\InvalidLeaveStatusTransitionException;
use App\Modules\Leave\Domain\ValueObjects\DurationUnit;
use App\Modules\Leave\Domain\ValueObjects\LeavePeriod;
use App\Modules\Leave\Domain\ValueObjects\LeaveStatus;
use Carbon\CarbonImmutable;

class LeaveRequest
{
    public function __construct(private LeaveRequestId $id, private string $employeeId, private LeaveTypeId $leaveTypeId, private LeavePeriod $period, private DurationUnit $durationUnit, private ?string $reason, private LeaveStatus $status = LeaveStatus::PENDING, private ?string $approvedBy = null, private ?CarbonImmutable $approvedAt = null, private ?string $rejectedReason = null, private ?int $balanceBefore = null) {}
    public function id(): LeaveRequestId { return $this->id; }
    public function employeeId(): string { return $this->employeeId; }
    public function leaveTypeId(): LeaveTypeId { return $this->leaveTypeId; }
    public function period(): LeavePeriod { return $this->period; }
    public function durationUnit(): DurationUnit { return $this->durationUnit; }
    public function reason(): ?string { return $this->reason; }
    public function status(): LeaveStatus { return $this->status; }
    public function approvedBy(): ?string { return $this->approvedBy; }
    public function approvedAt(): ?CarbonImmutable { return $this->approvedAt; }
    public function rejectedReason(): ?string { return $this->rejectedReason; }
    public function balanceBefore(): ?int { return $this->balanceBefore; }
    public function approve(string $userId, ?int $balanceBefore = null): LeaveRequestApproved { $this->requirePending(); $this->status = LeaveStatus::APPROVED; $this->approvedBy=$userId; $this->approvedAt=CarbonImmutable::now(); $this->balanceBefore=$balanceBefore; return new LeaveRequestApproved(['leave_request_id'=>$this->id->value(),'approved_by'=>$userId]); }
    public function reject(string $userId, string $reason): LeaveRequestRejected { $this->requirePending(); $this->status = LeaveStatus::REJECTED; $this->rejectedReason=$reason; return new LeaveRequestRejected(['leave_request_id'=>$this->id->value(),'rejected_by'=>$userId,'reason'=>$reason]); }
    public function cancel(string $userId): LeaveRequestCancelled { if (! in_array($this->status, [LeaveStatus::PENDING, LeaveStatus::APPROVED], true)) throw new InvalidLeaveStatusTransitionException('Only pending or approved leave can be cancelled'); $this->status = LeaveStatus::CANCELLED; return new LeaveRequestCancelled(['leave_request_id'=>$this->id->value(),'cancelled_by'=>$userId]); }
    public function submittedEvent(): LeaveRequestSubmitted { return new LeaveRequestSubmitted(['leave_request_id'=>$this->id->value(),'employee_id'=>$this->employeeId]); }
    private function requirePending(): void { if ($this->status !== LeaveStatus::PENDING) throw new InvalidLeaveStatusTransitionException('Leave request must be pending'); }
}
