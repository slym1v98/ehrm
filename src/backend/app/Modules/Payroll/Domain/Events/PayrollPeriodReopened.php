<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollPeriodReopened
{
    public function __construct(public string $periodId, public string $reopenedBy) {}
}
