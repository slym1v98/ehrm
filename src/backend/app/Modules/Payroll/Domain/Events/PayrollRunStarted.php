<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollRunStarted
{
    public function __construct(public string $runId, public string $periodId, public string $triggeredBy) {}
}
