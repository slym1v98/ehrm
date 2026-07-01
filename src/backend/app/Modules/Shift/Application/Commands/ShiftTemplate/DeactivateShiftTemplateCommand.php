<?php

namespace App\Modules\Shift\Application\Commands\ShiftTemplate;

final readonly class DeactivateShiftTemplateCommand
{
    public function __construct(public string $id) {}
}
