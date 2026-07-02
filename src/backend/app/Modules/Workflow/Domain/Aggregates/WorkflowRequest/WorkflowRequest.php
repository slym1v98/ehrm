<?php

namespace App\Modules\Workflow\Domain\Aggregates\WorkflowRequest;

use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Events\WorkflowApproved;
use App\Modules\Workflow\Domain\Events\WorkflowCancelled;
use App\Modules\Workflow\Domain\Events\WorkflowRejected;
use App\Modules\Workflow\Domain\Events\WorkflowReturnedForEdit;
use App\Modules\Workflow\Domain\Events\WorkflowStepCompleted;
use App\Modules\Workflow\Domain\Exceptions\InvalidWorkflowTransitionException;
use App\Modules\Workflow\Domain\ValueObjects\RequestStatus;
use App\Modules\Workflow\Domain\ValueObjects\WorkflowActionType;
use Carbon\CarbonImmutable;

class WorkflowRequest
{
    private RequestStatus $status;
    private ?int $currentStep;
    private array $actions;

    public function __construct(
        private WorkflowRequestId $id,
        private WorkflowTemplateId $workflowTemplateId,
        private string $subjectType,
        private string $subjectId,
        private string $submittedBy,
        ?RequestStatus $status = null,
        ?int $currentStep = null,
        array $actions = [],
    ) {
        $this->status = $status ?? RequestStatus::PENDING;
        $this->currentStep = $currentStep;
        $this->actions = $actions;
    }

    public function id(): WorkflowRequestId { return $this->id; }
    public function workflowTemplateId(): WorkflowTemplateId { return $this->workflowTemplateId; }
    public function subjectType(): string { return $this->subjectType; }
    public function subjectId(): string { return $this->subjectId; }
    public function submittedBy(): string { return $this->submittedBy; }
    public function status(): RequestStatus { return $this->status; }
    public function currentStep(): ?int { return $this->currentStep; }
    public function actions(): array { return $this->actions; }

    public function start(int $firstStepOrder): WorkflowStepCompleted
    {
        $this->assertStatus(RequestStatus::PENDING);
        $this->status = RequestStatus::IN_REVIEW;
        $this->currentStep = $firstStepOrder;
        $event = new WorkflowStepCompleted(['request_id' => $this->id->value(), 'step_order' => $firstStepOrder]);
        $this->actions[] = new WorkflowAction(
            WorkflowActionId::new(), $this->id,
            $firstStepOrder, WorkflowActionType::APPROVE, $this->submittedBy, 'Request submitted',
        );
        return $event;
    }

    public function approveStep(string $actorId, int $stepOrder, bool $isFinal, ?string $comment = null): array
    {
        $this->assertStatus(RequestStatus::IN_REVIEW);
        $this->assertCurrentStep($stepOrder);

        $events = [];
        $this->actions[] = new WorkflowAction(
            WorkflowActionId::new(), $this->id,
            $stepOrder, WorkflowActionType::APPROVE, $actorId, $comment,
        );

        if ($isFinal) {
            $this->status = RequestStatus::APPROVED;
            $this->currentStep = null;
            $events[] = new WorkflowApproved(['request_id' => $this->id->value()]);
        } else {
            $this->currentStep = $stepOrder + 1;
            $events[] = new WorkflowStepCompleted(['request_id' => $this->id->value(), 'step_order' => $stepOrder + 1]);
        }
        return $events;
    }

    public function rejectStep(string $actorId, int $stepOrder, string $comment): WorkflowRejected
    {
        $this->assertStatus(RequestStatus::IN_REVIEW);
        $this->assertCurrentStep($stepOrder);
        $this->status = RequestStatus::REJECTED;
        $this->currentStep = null;
        $this->actions[] = new WorkflowAction(
            WorkflowActionId::new(), $this->id,
            $stepOrder, WorkflowActionType::REJECT, $actorId, $comment,
        );
        return new WorkflowRejected(['request_id' => $this->id->value(), 'step_order' => $stepOrder]);
    }

    public function returnForEdit(string $actorId, int $stepOrder, string $comment): WorkflowReturnedForEdit
    {
        $this->assertStatus(RequestStatus::IN_REVIEW);
        $this->assertCurrentStep($stepOrder);
        $this->status = RequestStatus::RETURNED;
        $this->currentStep = null;
        $this->actions[] = new WorkflowAction(
            WorkflowActionId::new(), $this->id,
            $stepOrder, WorkflowActionType::RETURN_FOR_EDIT, $actorId, $comment,
        );
        return new WorkflowReturnedForEdit(['request_id' => $this->id->value(), 'step_order' => $stepOrder]);
    }

    public function cancel(string $actorId, ?string $comment = null): WorkflowCancelled
    {
        if (! in_array($this->status, [RequestStatus::PENDING, RequestStatus::IN_REVIEW, RequestStatus::RETURNED], true)) {
            throw new InvalidWorkflowTransitionException('Only pending, in_review, or returned requests can be cancelled');
        }
        $this->status = RequestStatus::CANCELLED;
        $this->currentStep = null;
        $this->actions[] = new WorkflowAction(
            WorkflowActionId::new(), $this->id,
            -1, WorkflowActionType::CANCEL, $actorId, $comment,
        );
        return new WorkflowCancelled(['request_id' => $this->id->value()]);
    }

    public function resubmit(int $firstStepOrder): WorkflowStepCompleted
    {
        $this->assertStatus(RequestStatus::RETURNED);
        $this->status = RequestStatus::IN_REVIEW;
        $this->currentStep = $firstStepOrder;
        return new WorkflowStepCompleted(['request_id' => $this->id->value(), 'step_order' => $firstStepOrder]);
    }

    private function assertStatus(RequestStatus $expected): void
    {
        if ($this->status !== $expected) {
            throw new InvalidWorkflowTransitionException("Expected status {$expected->value}, got {$this->status->value}");
        }
    }

    private function assertCurrentStep(int $stepOrder): void
    {
        if ($this->currentStep !== $stepOrder) {
            throw new InvalidWorkflowTransitionException("Current step is {$this->currentStep}, not {$stepOrder}");
        }
    }
}
