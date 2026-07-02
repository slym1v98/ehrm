<?php

namespace App\Modules\Payroll\Application\Queries;

use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Repositories\PayrollEntryRepositoryInterface;

readonly class PayrollSummaryQuery
{
    public function __construct(private PayrollEntryRepositoryInterface $entryRepo) {}

    /** @return array{total_entries:int, total_gross:float, total_net:float, error_count:int} */
    public function getSummary(string $periodId): array
    {
        $entries = $this->entryRepo->findByPeriod(PayrollPeriodId::fromString($periodId));
        $totalGross = 0.0; $totalNet = 0.0; $errors = 0;
        foreach ($entries as $e) {
            $totalGross += $e->getGrossAmount()->toDecimal();
            $totalNet += $e->getNetAmount()->toDecimal();
            if ($e->getStatus() === 'error') $errors++;
        }
        return [
            'total_entries' => count($entries),
            'total_gross' => $totalGross,
            'total_net' => $totalNet,
            'error_count' => $errors,
        ];
    }
}
