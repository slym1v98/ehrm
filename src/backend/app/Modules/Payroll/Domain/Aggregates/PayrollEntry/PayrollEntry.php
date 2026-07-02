<?php

namespace App\Modules\Payroll\Domain\Aggregates\PayrollEntry;

use App\Modules\Payroll\Domain\Aggregates\PayrollRun\PayrollRunId;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\ValueObjects\Money;
use App\Modules\Payroll\Domain\ValueObjects\PayrollFormulaResult;
use App\Modules\Payroll\Domain\Exceptions\InvalidPayrollCalculationException;
use DateTimeImmutable;

class PayrollEntry
{
    private function __construct(
        private PayrollEntryId $id,
        private PayrollRunId $runId,
        private PayrollPeriodId $periodId,
        private string $employeeId,
        private array $contractSnapshot,
        private array $attendanceSnapshot,
        private array $leaveSnapshot,
        private Money $grossAmount,
        private Money $deductionAmount,
        private Money $netAmount,
        private array $lines,
        private string $status,
        private ?string $errorMessage = null,
        private ?string $reviewedBy = null,
        private ?DateTimeImmutable $reviewedAt = null,
    ) {}

    public static function create(
        PayrollEntryId $id,
        PayrollRunId $runId,
        PayrollPeriodId $periodId,
        string $employeeId,
        array $contractSnapshot,
        array $attendanceSnapshot,
        array $leaveSnapshot,
        PayrollFormulaResult $result,
    ): self {
        if ($result->net->isNegative()) {
            throw new InvalidPayrollCalculationException('Net amount cannot be negative for employee '.$employeeId);
        }
        return new self(
            id: $id,
            runId: $runId,
            periodId: $periodId,
            employeeId: $employeeId,
            contractSnapshot: $contractSnapshot,
            attendanceSnapshot: $attendanceSnapshot,
            leaveSnapshot: $leaveSnapshot,
            grossAmount: $result->gross,
            deductionAmount: $result->deduction,
            netAmount: $result->net,
            lines: $result->lines,
            status: 'calculated',
        );
    }

    public static function createFailed(
        PayrollEntryId $id,
        PayrollRunId $runId,
        PayrollPeriodId $periodId,
        string $employeeId,
        string $errorMessage,
    ): self {
        return new self(
            id: $id,
            runId: $runId,
            periodId: $periodId,
            employeeId: $employeeId,
            contractSnapshot: [],
            attendanceSnapshot: [],
            leaveSnapshot: [],
            grossAmount: Money::zero(),
            deductionAmount: Money::zero(),
            netAmount: Money::zero(),
            lines: [],
            status: 'error',
            errorMessage: $errorMessage,
        );
    }

    public function review(string $reviewedBy): void
    {
        if ($this->status !== 'calculated') {
            throw new \RuntimeException('Only calculated entries can be reviewed.');
        }
        $this->status = 'reviewed';
        $this->reviewedBy = $reviewedBy;
        $this->reviewedAt = new DateTimeImmutable();
    }

    public function applyAdjustment(Money $delta): void
    {
        // Simple aggregate change: add delta to net (positive = increase, negative = decrease)
        $this->netAmount = $this->netAmount->add($delta);
        if ($this->netAmount->isNegative()) {
            throw new InvalidPayrollCalculationException('Adjustment would make net negative.');
        }
    }

    public function getId(): PayrollEntryId { return $this->id; }
    public function getRunId(): PayrollRunId { return $this->runId; }
    public function getPeriodId(): PayrollPeriodId { return $this->periodId; }
    public function getEmployeeId(): string { return $this->employeeId; }
    public function getContractSnapshot(): array { return $this->contractSnapshot; }
    public function getAttendanceSnapshot(): array { return $this->attendanceSnapshot; }
    public function getLeaveSnapshot(): array { return $this->leaveSnapshot; }
    public function getGrossAmount(): Money { return $this->grossAmount; }
    public function getDeductionAmount(): Money { return $this->deductionAmount; }
    public function getNetAmount(): Money { return $this->netAmount; }
    public function getLines(): array { return $this->lines; }
    public function getStatus(): string { return $this->status; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function getReviewedBy(): ?string { return $this->reviewedBy; }
    public function getReviewedAt(): ?DateTimeImmutable { return $this->reviewedAt; }
}
