<?php

namespace App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate;

use App\Modules\Workflow\Domain\ValueObjects\AssigneeType;

class WorkflowStep
{
    public function __construct(
        private WorkflowStepId $id,
        private int $stepOrder,
        private string $name,
        private AssigneeType $assigneeType,
        private ?string $assigneeId = null,
        private ?array $condition = null,
    ) {}

    public function id(): WorkflowStepId { return $this->id; }
    public function stepOrder(): int { return $this->stepOrder; }
    public function name(): string { return $this->name; }
    public function assigneeType(): AssigneeType { return $this->assigneeType; }
    public function assigneeId(): ?string { return $this->assigneeId; }
    public function condition(): ?array { return $this->condition; }
}
