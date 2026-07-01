<?php

namespace App\Modules\Configuration\Domain\Events;

use DateTimeImmutable;

final readonly class CodeGenerationRuleChanged
{
    public function __construct(public string $ruleId, public string $action, public DateTimeImmutable $occurredAt) {}
}
