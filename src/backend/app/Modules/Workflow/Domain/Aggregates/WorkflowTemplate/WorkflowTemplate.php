<?php

namespace App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate;

use App\Modules\Workflow\Domain\Exceptions\InvalidWorkflowTransitionException;

class WorkflowTemplate
{
    /** @param WorkflowStep[] $steps */
    public function __construct(
        private WorkflowTemplateId $id,
        private string $code,
        private string $name,
        private ?string $description,
        private bool $active,
        private array $steps,
    ) {
        $this->assertValidSteps($steps);
        usort($this->steps, fn (WorkflowStep $a, WorkflowStep $b) => $a->stepOrder() <=> $b->stepOrder());
    }

    public function id(): WorkflowTemplateId { return $this->id; }
    public function code(): string { return $this->code; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function isActive(): bool { return $this->active; }
    /** @return WorkflowStep[] */ public function steps(): array { return $this->steps; }
    public function activate(): void { $this->active = true; }
    public function deactivate(): void { $this->active = false; }
    public function firstStep(): WorkflowStep { return $this->steps[0]; }
    public function nextStepAfter(int $stepOrder): ?WorkflowStep
    {
        foreach ($this->steps as $step) {
            if ($step->stepOrder() === $stepOrder + 1) {
                return $step;
            }
        }
        return null;
    }
    public function isFinalStep(int $stepOrder): bool { return $this->nextStepAfter($stepOrder) === null; }

    private function assertValidSteps(array $steps): void
    {
        if ($steps === []) {
            throw new InvalidWorkflowTransitionException('Workflow template requires at least one step');
        }
        $orders = array_map(fn (WorkflowStep $step) => $step->stepOrder(), $steps);
        sort($orders);
        foreach ($orders as $index => $order) {
            if ($order !== $index + 1) {
                throw new InvalidWorkflowTransitionException('Workflow step order must start at 1 and have no gaps');
            }
        }
        if (count($orders) !== count(array_unique($orders))) {
            throw new InvalidWorkflowTransitionException('Workflow step order must be unique');
        }
    }
}
