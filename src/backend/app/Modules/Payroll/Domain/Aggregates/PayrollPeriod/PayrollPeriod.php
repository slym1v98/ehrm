<?php

namespace App\Modules\Payroll\Domain\Aggregates\PayrollPeriod;

use App\Modules\Payroll\Domain\ValueObjects\PeriodStatus;
use App\Modules\Payroll\Domain\Exceptions\PayrollPeriodLockedException;
use App\Modules\Payroll\Domain\Exceptions\PayrollPeriodClosedException;
use App\Modules\Payroll\Domain\Exceptions\PayrollNotApprovedException;
use App\Modules\Payroll\Domain\Exceptions\PayrollAlreadyPublishedException;
use App\Modules\Payroll\Domain\Events\PayrollPeriodOpened;
use App\Modules\Payroll\Domain\Events\PayrollPeriodClosed;
use App\Modules\Payroll\Domain\Events\PayrollPeriodReopened;
use App\Modules\Payroll\Domain\Events\PayrollRunStarted;
use App\Modules\Payroll\Domain\Events\PayrollApproved;
use App\Modules\Payroll\Domain\Events\PayrollLocked;
use App\Modules\Payroll\Domain\Events\PayrollPublished;
use DateTimeImmutable;

class PayrollPeriod
{
    private array $recordedEvents = [];

    private function __construct(
        private PayrollPeriodId $id,
        private string $periodCode,
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate,
        private DateTimeImmutable $cutoffDate,
        private PeriodStatus $status,
        private ?string $attendancePeriodId,
        private ?string $workflowRequestId,
        private string $openedBy,
        private DateTimeImmutable $openedAt,
        private ?string $approvedBy = null,
        private ?DateTimeImmutable $approvedAt = null,
        private ?string $lockedBy = null,
        private ?DateTimeImmutable $lockedAt = null,
        private ?DateTimeImmutable $publishedAt = null,
    ) {}

    public static function open(
        PayrollPeriodId $id,
        string $periodCode,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        DateTimeImmutable $cutoffDate,
        ?string $attendancePeriodId,
        string $openedBy,
    ): self {
        $period = new self(
            id: $id,
            periodCode: $periodCode,
            startDate: $startDate,
            endDate: $endDate,
            cutoffDate: $cutoffDate,
            status: PeriodStatus::Open,
            attendancePeriodId: $attendancePeriodId,
            workflowRequestId: null,
            openedBy: $openedBy,
            openedAt: new DateTimeImmutable(),
        );
        $period->recordedEvents[] = new PayrollPeriodOpened($id->value, $openedBy);
        return $period;
    }

    public function startRun(string $triggeredBy): PayrollRunStarted
    {
        $this->guardTransition(PeriodStatus::Calculating);
        $this->status = PeriodStatus::Calculating;
        return new PayrollRunStarted($this->id->value, $this->id->value, $triggeredBy);
    }

    public function completeRun(): void
    {
        $this->guardTransition(PeriodStatus::Completed);
        $this->status = PeriodStatus::Completed;
    }

    public function submitForApproval(string $workflowRequestId): void
    {
        $this->guardTransition(PeriodStatus::Reviewing);
        $this->status = PeriodStatus::Reviewing;
        $this->workflowRequestId = $workflowRequestId;
    }

    public function approve(string $approvedBy): PayrollApproved
    {
        $this->guardTransition(PeriodStatus::Approved);
        $this->status = PeriodStatus::Approved;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new DateTimeImmutable();
        return new PayrollApproved($this->id->value, $approvedBy);
    }

    public function reject(): void
    {
        $this->guardTransition(PeriodStatus::Completed);
        $this->status = PeriodStatus::Completed;
    }

    public function lock(string $lockedBy): PayrollLocked
    {
        if ($this->status !== PeriodStatus::Approved) {
            throw PayrollNotApprovedException::default();
        }
        $this->guardTransition(PeriodStatus::Locked);
        $this->status = PeriodStatus::Locked;
        $this->lockedBy = $lockedBy;
        $this->lockedAt = new DateTimeImmutable();
        return new PayrollLocked($this->id->value, $lockedBy);
    }

    public function publish(string $publishedBy): PayrollPublished
    {
        if ($this->status !== PeriodStatus::Locked) {
            throw PayrollPeriodLockedException::default();
        }
        $this->guardTransition(PeriodStatus::Published);
        $this->status = PeriodStatus::Published;
        $this->publishedAt = new DateTimeImmutable();
        return new PayrollPublished($this->id->value, $publishedBy);
    }

    public function reopen(string $reopenedBy): PayrollPeriodReopened
    {
        // Privileged: only from Locked back to Reviewing
        if ($this->status !== PeriodStatus::Locked) {
            throw PayrollPeriodLockedException::default();
        }
        if ($this->publishedAt !== null) {
            throw PayrollAlreadyPublishedException::default();
        }
        $this->status = PeriodStatus::Reviewing;
        return new PayrollPeriodReopened($this->id->value, $reopenedBy);
    }

    private function guardTransition(PeriodStatus $target): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw new \RuntimeException(
                "Cannot transition from {$this->status->value} to {$target->value}."
            );
        }
    }

    public function isLocked(): bool
    {
        return $this->status === PeriodStatus::Locked || $this->status === PeriodStatus::Published;
    }

    public function isModifiable(): bool
    {
        return !in_array($this->status, [PeriodStatus::Locked, PeriodStatus::Published], true);
    }

    // Getters for persistence
    public function getId(): PayrollPeriodId { return $this->id; }
    public function getPeriodCode(): string { return $this->periodCode; }
    public function getStartDate(): DateTimeImmutable { return $this->startDate; }
    public function getEndDate(): DateTimeImmutable { return $this->endDate; }
    public function getCutoffDate(): DateTimeImmutable { return $this->cutoffDate; }
    public function getStatus(): PeriodStatus { return $this->status; }
    public function getAttendancePeriodId(): ?string { return $this->attendancePeriodId; }
    public function getWorkflowRequestId(): ?string { return $this->workflowRequestId; }
    public function getOpenedBy(): string { return $this->openedBy; }
    public function getOpenedAt(): DateTimeImmutable { return $this->openedAt; }
    public function getApprovedBy(): ?string { return $this->approvedBy; }
    public function getApprovedAt(): ?DateTimeImmutable { return $this->approvedAt; }
    public function getLockedBy(): ?string { return $this->lockedBy; }
    public function getLockedAt(): ?DateTimeImmutable { return $this->lockedAt; }
    public function getPublishedAt(): ?DateTimeImmutable { return $this->publishedAt; }
    public function getRecordedEvents(): array { return $this->recordedEvents; }
    public function clearRecordedEvents(): void { $this->recordedEvents = []; }
}
