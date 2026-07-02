<?php
namespace Tests\Unit\Modules\Workflow\Domain;

use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequest;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequestId;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Events\WorkflowApproved;
use App\Modules\Workflow\Domain\Events\WorkflowCancelled;
use App\Modules\Workflow\Domain\Events\WorkflowRejected;
use App\Modules\Workflow\Domain\Events\WorkflowReturnedForEdit;
use App\Modules\Workflow\Domain\Events\WorkflowStepCompleted;
use App\Modules\Workflow\Domain\Exceptions\InvalidWorkflowTransitionException;
use App\Modules\Workflow\Domain\ValueObjects\RequestStatus;
use Tests\TestCase;

class WorkflowRequestStateMachineTest extends TestCase
{
    private function makeRequest(): WorkflowRequest
    {
        return new WorkflowRequest(
            new WorkflowRequestId('00000000-0000-0000-0000-000000000010'),
            new WorkflowTemplateId('00000000-0000-0000-0000-000000000011'),
            'leave_request',
            '00000000-0000-0000-0000-000000000012',
            '00000000-0000-0000-0000-000000000013',
        );
    }

    public function test_starts_as_pending(): void
    {
        $r = $this->makeRequest();
        $this->assertEquals(RequestStatus::PENDING, $r->status());
        $this->assertNull($r->currentStep());
    }

    public function test_start_transitions_to_in_review(): void
    {
        $r = $this->makeRequest();
        $event = $r->start(1);
        $this->assertEquals(RequestStatus::IN_REVIEW, $r->status());
        $this->assertSame(1, $r->currentStep());
        $this->assertInstanceOf(WorkflowStepCompleted::class, $event);
    }

    public function test_approve_non_final_step_advances(): void
    {
        $r = $this->makeRequest();
        $r->start(1);
        $events = $r->approveStep('actor-1', 1, false);
        $this->assertEquals(RequestStatus::IN_REVIEW, $r->status());
        $this->assertSame(2, $r->currentStep());
        $this->assertInstanceOf(WorkflowStepCompleted::class, $events[0]);
    }

    public function test_approve_final_step_sets_approved(): void
    {
        $r = $this->makeRequest();
        $r->start(1);
        $events = $r->approveStep('actor-1', 1, true);
        $this->assertEquals(RequestStatus::APPROVED, $r->status());
        $this->assertNull($r->currentStep());
        $this->assertInstanceOf(WorkflowApproved::class, $events[0]);
    }

    public function test_reject_sets_rejected(): void
    {
        $r = $this->makeRequest();
        $r->start(1);
        $event = $r->rejectStep('actor-1', 1, 'No');
        $this->assertEquals(RequestStatus::REJECTED, $r->status());
        $this->assertInstanceOf(WorkflowRejected::class, $event);
    }

    public function test_return_for_edit_sets_returned(): void
    {
        $r = $this->makeRequest();
        $r->start(1);
        $event = $r->returnForEdit('actor-1', 1, 'Edit');
        $this->assertEquals(RequestStatus::RETURNED, $r->status());
        $this->assertInstanceOf(WorkflowReturnedForEdit::class, $event);
    }

    public function test_cancel_pending(): void
    {
        $r = $this->makeRequest();
        $event = $r->cancel('actor-1');
        $this->assertEquals(RequestStatus::CANCELLED, $r->status());
        $this->assertInstanceOf(WorkflowCancelled::class, $event);
    }

    public function test_cancel_in_review(): void
    {
        $r = $this->makeRequest();
        $r->start(1);
        $r->cancel('actor-1');
        $this->assertEquals(RequestStatus::CANCELLED, $r->status());
    }

    public function test_cancel_returned(): void
    {
        $r = $this->makeRequest();
        $r->start(1);
        $r->returnForEdit('actor-1', 1, 'Edit');
        $r->cancel('actor-1');
        $this->assertEquals(RequestStatus::CANCELLED, $r->status());
    }

    public function test_cancel_approved_throws(): void
    {
        $this->expectException(InvalidWorkflowTransitionException::class);
        $r = $this->makeRequest();
        $r->start(1);
        $r->approveStep('actor-1', 1, true);
        $r->cancel('actor-1');
    }

    public function test_act_on_non_current_step_throws(): void
    {
        $this->expectException(InvalidWorkflowTransitionException::class);
        $r = $this->makeRequest();
        $r->start(1);
        $r->approveStep('actor-1', 2, false);
    }

    public function test_action_logged_on_every_transition(): void
    {
        $r = $this->makeRequest();
        $r->start(1);
        $this->assertCount(1, $r->actions());
        $r->approveStep('actor-1', 1, true);
        $this->assertCount(2, $r->actions());
    }

    public function test_resubmit_from_returned(): void
    {
        $r = $this->makeRequest();
        $r->start(1);
        $r->returnForEdit('actor-1', 1, 'Edit');
        $event = $r->resubmit(1);
        $this->assertEquals(RequestStatus::IN_REVIEW, $r->status());
        $this->assertSame(1, $r->currentStep());
        $this->assertInstanceOf(WorkflowStepCompleted::class, $event);
    }
}
