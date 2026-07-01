<?php

namespace App\Modules\Shift\Application\Commands\ShiftTemplate;

final readonly class ActivateShiftTemplateCommand
{
    public function __construct(public string $id) {}
}
