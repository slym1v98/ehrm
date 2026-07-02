<?php

namespace App\Modules\Payroll\Domain\Aggregates\PayrollRun;

use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\ValueObjects\RunStatus;
use DateTimeImmutable;

class PayrollRun
{
    private function __construct(
        private PayrollRunId $id,
        private PayrollPeriodId $periodId,
        private string $runType,
        private RunStatus $status,
        private string $formulaVersion,
        private string $triggeredBy,
        private DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $completedAt = null,
        private ?string $errorSummary = null,
    ) {}

    public static function start(
        PayrollRunId $id,
        PayrollPeriodId $periodId,
        string $runType,
        string $formulaVersion,
        string $triggeredBy,
    ): self {
        return new self(
            id: $id,
            periodId: $periodId,
            runType: $runType,
            status: RunStatus::Running,
            formulaVersion: $formulaVersion,
            triggeredBy: $triggeredBy,
            startedAt: new DateTimeImmutable(),
        );
    }

    public function complete(?string $errorSummary = null): void
    {
        $this->status = $errorSummary !== null ? RunStatus::Failed : RunStatus::Completed;
        $this->completedAt = new DateTimeImmutable();
        $this->errorSummary = $errorSummary;
    }

    public function fail(string $errorSummary): void
    {
        $this->status = RunStatus::Failed;
        $this->completedAt = new DateTimeImmutable();
        $this->errorSummary = $errorSummary;
    }

    public function getId(): PayrollRunId { return $this->id; }
    public function getPeriodId(): PayrollPeriodId { return $this->periodId; }
    public function getRunType(): string { return $this->runType; }
    public function getStatus(): RunStatus { return $this->status; }
    public function getFormulaVersion(): string { return $this->formulaVersion; }
    public function getTriggeredBy(): string { return $this->triggeredBy; }
    public function getStartedAt(): DateTimeImmutable { return $this->startedAt; }
    public function getCompletedAt(): ?DateTimeImmutable { return $this->completedAt; }
    public function getErrorSummary(): ?string { return $this->errorSummary; }
}
