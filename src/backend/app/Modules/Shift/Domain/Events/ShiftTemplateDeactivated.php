<?php

namespace App\Modules\Shift\Domain\Events;

use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use DateTimeImmutable;

final readonly class ShiftTemplateDeactivated
{
    public function __construct(public ShiftTemplateId $shiftTemplateId, DateTimeImmutable $occurredAt) {}
}
