<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollPeriodClosed
{
    public function __construct(public string $periodId) {}
}
