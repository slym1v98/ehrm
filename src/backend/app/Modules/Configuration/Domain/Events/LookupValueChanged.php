<?php

namespace App\Modules\Configuration\Domain\Events;

use DateTimeImmutable;

final readonly class LookupValueChanged
{
    public function __construct(public string $groupId, public string $valueId, public string $action, public DateTimeImmutable $occurredAt) {}
}
