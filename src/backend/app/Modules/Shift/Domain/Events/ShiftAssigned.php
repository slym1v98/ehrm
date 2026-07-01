<?php

namespace App\Modules\Shift\Domain\Events;

use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use DateTimeImmutable;

final readonly class ShiftAssigned
{
    public function __construct(public ShiftAssignmentId $assignmentId, public ShiftTemplateId $shiftTemplateId, public string $assignableType, public string $assignableId, public string $effectiveFrom, DateTimeImmutable $occurredAt) {}
}
