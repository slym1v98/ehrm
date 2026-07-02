<?php

namespace App\Modules\Reporting\Domain\Aggregates\ReportRun;

use App\Modules\Reporting\Domain\ValueObjects\ReportRunStatus;
use Carbon\CarbonImmutable;

class ReportRun
{
    private function __construct(
        private readonly ReportRunId $id,
        private string $reportDefinitionId,
        private string $requestedBy,
        private array $filters,
        private ReportRunStatus $status,
        private ?array $result,
        private ?string $error,
        private ?CarbonImmutable $startedAt,
        private ?CarbonImmutable $completedAt,
    ) {}

    public static function request(
        ReportRunId $id,
        string $reportDefinitionId,
        string $requestedBy,
        array $filters = [],
    ): self {
        return new self($id, $reportDefinitionId, $requestedBy, $filters, ReportRunStatus::Requested, null, null, null, null);
    }

    public static function reconstitute(
        ReportRunId $id,
        string $reportDefinitionId,
        string $requestedBy,
        array $filters,
        ReportRunStatus $status,
        ?array $result,
        ?string $error,
        ?CarbonImmutable $startedAt,
        ?CarbonImmutable $completedAt,
    ): self {
        return new self($id, $reportDefinitionId, $requestedBy, $filters, $status, $result, $error, $startedAt, $completedAt);
    }

    public function start(CarbonImmutable $at): void
    {
        if (!$this->status->canTransitionTo(ReportRunStatus::Running)) {
            throw new \InvalidArgumentException('Cannot transition from ' . $this->status->value . ' to running');
        }
        $this->status = ReportRunStatus::Running;
        $this->startedAt = $at;
    }

    public function complete(array $result, CarbonImmutable $at): void
    {
        if (!$this->status->canTransitionTo(ReportRunStatus::Completed)) {
            throw new \InvalidArgumentException('Cannot transition from ' . $this->status->value . ' to completed');
        }
        $this->status = ReportRunStatus::Completed;
        $this->result = $result;
        $this->completedAt = $at;
    }

    public function fail(string $error, CarbonImmutable $at): void
    {
        if ($this->status === ReportRunStatus::Failed) return;
        $this->status = ReportRunStatus::Failed;
        $this->error = $error;
        $this->completedAt = $at;
    }

    public function getId(): ReportRunId { return $this->id; }
    public function getReportDefinitionId(): string { return $this->reportDefinitionId; }
    public function getRequestedBy(): string { return $this->requestedBy; }
    public function getFilters(): array { return $this->filters; }
    public function getStatus(): ReportRunStatus { return $this->status; }
    public function getResult(): ?array { return $this->result; }
    public function getError(): ?string { return $this->error; }
    public function getStartedAt(): ?CarbonImmutable { return $this->startedAt; }
    public function getCompletedAt(): ?CarbonImmutable { return $this->completedAt; }
}
