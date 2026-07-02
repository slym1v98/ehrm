<?php
namespace App\Modules\Payroll\Application\Commands\PayrollPeriod;
use DateTimeImmutable;
readonly class OpenPayrollPeriodCommand
{
    public function __construct(
        public string $periodCode,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public DateTimeImmutable $cutoffDate,
        public ?string $attendancePeriodId,
        public string $openedBy,
    ) {}
}
