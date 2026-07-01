<?php

namespace App\Modules\Shift\Application\Commands\ShiftAssignment;

final readonly class ChangeShiftAssignmentCommand
{
    public function __construct(public string $id, public string $newTemplateId, public string $effectiveFrom) {}
}
