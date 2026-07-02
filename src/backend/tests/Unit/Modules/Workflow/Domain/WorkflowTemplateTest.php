<?php
namespace Tests\Unit\Modules\Workflow\Domain;

use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowStep;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowStepId;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplate;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Exceptions\InvalidWorkflowTransitionException;
use App\Modules\Workflow\Domain\ValueObjects\AssigneeType;
use Tests\TestCase;

class WorkflowTemplateTest extends TestCase
{
    public function test_creates_template_with_ordered_steps(): void
    {
        $template = new WorkflowTemplate(new WorkflowTemplateId('00000000-0000-0000-0000-000000000001'), 'leave.default', 'Leave Approval', null, true, [
            new WorkflowStep(new WorkflowStepId('00000000-0000-0000-0000-000000000002'), 1, 'Manager', AssigneeType::ROLE, 'role-1'),
            new WorkflowStep(new WorkflowStepId('00000000-0000-0000-0000-000000000003'), 2, 'Director', AssigneeType::ROLE, 'role-2'),
        ]);
        $this->assertSame(1, $template->firstStep()->stepOrder());
        $this->assertSame(2, $template->nextStepAfter(1)->stepOrder());
        $this->assertNull($template->nextStepAfter(2));
        $this->assertFalse($template->isFinalStep(1));
        $this->assertTrue($template->isFinalStep(2));
    }

    public function test_template_with_gaps_throws(): void
    {
        $this->expectException(InvalidWorkflowTransitionException::class);
        new WorkflowTemplate(new WorkflowTemplateId('00000000-0000-0000-0000-000000000001'), 'test', 'Test', null, true, [
            new WorkflowStep(new WorkflowStepId('00000000-0000-0000-0000-000000000002'), 1, 'S1', AssigneeType::ROLE),
            new WorkflowStep(new WorkflowStepId('00000000-0000-0000-0000-000000000003'), 3, 'S3', AssigneeType::ROLE),
        ]);
    }

    public function test_template_with_duplicate_steps_throws(): void
    {
        $this->expectException(InvalidWorkflowTransitionException::class);
        new WorkflowTemplate(new WorkflowTemplateId('00000000-0000-0000-0000-000000000001'), 'test', 'Test', null, true, [
            new WorkflowStep(new WorkflowStepId('00000000-0000-0000-0000-000000000002'), 1, 'S1', AssigneeType::ROLE),
            new WorkflowStep(new WorkflowStepId('00000000-0000-0000-0000-000000000003'), 1, 'S1b', AssigneeType::ROLE),
        ]);
    }

    public function test_template_activates_and_deactivates(): void
    {
        $template = new WorkflowTemplate(new WorkflowTemplateId('00000000-0000-0000-0000-000000000001'), 'test', 'Test', null, true, [
            new WorkflowStep(new WorkflowStepId('00000000-0000-0000-0000-000000000002'), 1, 'S1', AssigneeType::ROLE),
        ]);
        $this->assertTrue($template->isActive());
        $template->deactivate();
        $this->assertFalse($template->isActive());
        $template->activate();
        $this->assertTrue($template->isActive());
    }
}
