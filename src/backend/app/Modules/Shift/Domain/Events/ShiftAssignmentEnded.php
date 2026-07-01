<?php

namespace App\Modules\Shift\Domain\Events;

use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use DateTimeImmutable;

final readonly class ShiftAssignmentEnded
{
    public function __construct(public ShiftAssignmentId $assignmentId, public ?string $effectiveTo, DateTimeImmutable $occurredAt) {}
}
