<?php

namespace App\Modules\Shift\Application\Commands\ShiftAssignment;

final readonly class AssignShiftCommand
{
    public function __construct(
        public string $shiftTemplateId,
        public string $assignableType,
        public string $assignableId,
        public string $effectiveFrom,
        public ?string $effectiveTo = null,
        public ?array $recurrenceRule = null,
    ) {}
}
