<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollRunCompleted
{
    public function __construct(
        public string $runId,
        public string $periodId,
        public int $totalEntries,
        public int $errorCount,
    ) {}
}
