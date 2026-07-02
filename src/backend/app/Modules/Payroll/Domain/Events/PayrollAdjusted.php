<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollAdjusted
{
    public function __construct(public string $adjustmentId, public string $entryId, public string $adjustedBy) {}
}
