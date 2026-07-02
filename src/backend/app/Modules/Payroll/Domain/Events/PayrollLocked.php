<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollLocked
{
    public function __construct(public string $periodId, public string $lockedBy) {}
}
