<?php

namespace App\Modules\Shift\Domain\Events;

use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use DateTimeImmutable;

final readonly class ShiftTemplateCreated
{
    public function __construct(public ShiftTemplateId $shiftTemplateId, public string $code, public string $name, public DateTimeImmutable $occurredAt) {}
}
