<?php

namespace Tests\Unit\Modules\Shift\Domain;

use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\RecurrenceRule;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignment;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Events\ShiftAssigned;
use DateTimeImmutable;
use Tests\TestCase;

class ShiftAssignmentTest extends TestCase
{
    public function test_assign_emits_event(): void
    {
        $assignment = ShiftAssignment::assign(
            ShiftAssignmentId::generate(),
            ShiftTemplateId::generate(),
            'employee',
            '00000000-0000-4000-8000-000000000001',
            new DateTimeImmutable('2026-07-01'),
            null,
            new RecurrenceRule('weekly', 1, [1, 2, 3], null),
        );

        $events = $assignment->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ShiftAssigned::class, $events[0]);
        $this->assertTrue($assignment->active());
    }

    public function test_end_assignment_sets_date_and_inactive(): void
    {
        $assignment = ShiftAssignment::assign(
            ShiftAssignmentId::generate(),
            ShiftTemplateId::generate(),
            'department',
            '00000000-0000-4000-8000-000000000002',
            new DateTimeImmutable('2026-07-01'),
            null,
            null,
        );

        $assignment->endAssignment(new DateTimeImmutable('2026-07-31'));

        $this->assertFalse($assignment->active());
        $this->assertSame('2026-07-31', $assignment->effectiveTo()?->format('Y-m-d'));
    }
}
