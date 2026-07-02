<?php

namespace App\Modules\Workflow\Domain\Aggregates\WorkflowRequest;

use App\Modules\Workflow\Domain\ValueObjects\WorkflowActionType;
use Carbon\CarbonImmutable;

class WorkflowAction
{
    public function __construct(
        private WorkflowActionId $id,
        private WorkflowRequestId $workflowRequestId,
        private int $stepOrder,
        private WorkflowActionType $action,
        private string $actorId,
        private ?string $comment,
        private array $metadata = [],
        private ?CarbonImmutable $createdAt = null,
    ) {
        $this->createdAt ??= CarbonImmutable::now();
    }

    public function id(): WorkflowActionId { return $this->id; }
    public function workflowRequestId(): WorkflowRequestId { return $this->workflowRequestId; }
    public function stepOrder(): int { return $this->stepOrder; }
    public function action(): WorkflowActionType { return $this->action; }
    public function actorId(): string { return $this->actorId; }
    public function comment(): ?string { return $this->comment; }
    public function metadata(): array { return $this->metadata; }
    public function createdAt(): CarbonImmutable { return $this->createdAt; }
}
