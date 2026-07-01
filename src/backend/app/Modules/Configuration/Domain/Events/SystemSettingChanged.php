<?php

namespace App\Modules\Configuration\Domain\Events;

use DateTimeImmutable;

final readonly class SystemSettingChanged
{
    public function __construct(public string $settingId, public string $key, public string $action, public DateTimeImmutable $occurredAt) {}
}
