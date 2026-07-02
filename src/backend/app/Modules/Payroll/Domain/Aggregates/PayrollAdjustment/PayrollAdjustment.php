<?php

namespace App\Modules\Payroll\Domain\Aggregates\PayrollAdjustment;

use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
use App\Modules\Payroll\Domain\ValueObjects\AdjustmentStatus;
use App\Modules\Payroll\Domain\ValueObjects\Money;
use App\Modules\Payroll\Domain\Events\PayrollAdjusted;
use DateTimeImmutable;

class PayrollAdjustment
{
    private function __construct(
        private PayrollAdjustmentId $id,
        private PayrollEntryId $entryId,
        private ?string $componentId,
        private string $adjustmentType,
        private Money $amount,
        private string $reason,
        private AdjustmentStatus $status,
        private string $submittedBy,
        private DateTimeImmutable $submittedAt,
        private ?string $approvedBy = null,
        private ?DateTimeImmutable $approvedAt = null,
        private ?string $rejectedReason = null,
    ) {}

    public static function submit(
        PayrollAdjustmentId $id,
        PayrollEntryId $entryId,
        ?string $componentId,
        string $adjustmentType,
        Money $amount,
        string $reason,
        string $submittedBy,
    ): self {
        if (!in_array($adjustmentType, ['add', 'subtract', 'override'], true)) {
            throw new \InvalidArgumentException('Invalid adjustment_type.');
        }
        return new self(
            id: $id,
            entryId: $entryId,
            componentId: $componentId,
            adjustmentType: $adjustmentType,
            amount: $amount,
            reason: $reason,
            status: AdjustmentStatus::Pending,
            submittedBy: $submittedBy,
            submittedAt: new DateTimeImmutable(),
        );
    }

    public function approve(string $approvedBy): PayrollAdjusted
    {
        if (!$this->status->canTransitionTo(AdjustmentStatus::Approved)) {
            throw new \RuntimeException("Cannot approve adjustment in status {$this->status->value}.");
        }
        $this->status = AdjustmentStatus::Approved;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new DateTimeImmutable();
        return new PayrollAdjusted($this->id->value, $this->entryId->value, $approvedBy);
    }

    public function reject(string $rejectedBy, string $reason): void
    {
        if (!$this->status->canTransitionTo(AdjustmentStatus::Rejected)) {
            throw new \RuntimeException("Cannot reject adjustment in status {$this->status->value}.");
        }
        $this->status = AdjustmentStatus::Rejected;
        $this->approvedBy = $rejectedBy;
        $this->approvedAt = new DateTimeImmutable();
        $this->rejectedReason = $reason;
    }

    public function getDelta(): Money
    {
        return match ($this->adjustmentType) {
            'add' => $this->amount,
            'subtract' => new Money(-$this->amount->getAmount(), $this->amount->getCurrency()),
            'override' => $this->amount,
            default => Money::zero(),
        };
    }

    public function getId(): PayrollAdjustmentId { return $this->id; }
    public function getEntryId(): PayrollEntryId { return $this->entryId; }
    public function getComponentId(): ?string { return $this->componentId; }
    public function getAdjustmentType(): string { return $this->adjustmentType; }
    public function getAmount(): Money { return $this->amount; }
    public function getReason(): string { return $this->reason; }
    public function getStatus(): AdjustmentStatus { return $this->status; }
    public function getSubmittedBy(): string { return $this->submittedBy; }
    public function getSubmittedAt(): DateTimeImmutable { return $this->submittedAt; }
    public function getApprovedBy(): ?string { return $this->approvedBy; }
    public function getApprovedAt(): ?DateTimeImmutable { return $this->approvedAt; }
    public function getRejectedReason(): ?string { return $this->rejectedReason; }
}
