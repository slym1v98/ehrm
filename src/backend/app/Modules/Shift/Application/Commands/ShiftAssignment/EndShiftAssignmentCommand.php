<?php

namespace App\Modules\Shift\Application\Commands\ShiftAssignment;

final readonly class EndShiftAssignmentCommand
{
    public function __construct(public string $id, public string $effectiveTo) {}
}
