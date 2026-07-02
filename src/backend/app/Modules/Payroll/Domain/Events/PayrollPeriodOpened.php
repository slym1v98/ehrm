<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollPeriodOpened
{
    public function __construct(public string $periodId, public string $openedBy) {}
}
