<?php

namespace App\Modules\Shift\Domain\Events;

use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use DateTimeImmutable;

final readonly class ShiftAssignmentChanged
{
    public function __construct(public ShiftAssignmentId $assignmentId, public ShiftTemplateId $oldTemplateId, public ShiftTemplateId $newTemplateId, DateTimeImmutable $occurredAt) {}
}
