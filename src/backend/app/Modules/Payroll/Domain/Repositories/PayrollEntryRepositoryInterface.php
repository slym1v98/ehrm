<?php
namespace App\Modules\Payroll\Domain\Repositories;
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\{PayrollEntry, PayrollEntryId};
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
interface PayrollEntryRepositoryInterface
{
    public function save(PayrollEntry $entry): void;
    public function findById(PayrollEntryId $id): ?PayrollEntry;
    /** @return PayrollEntry[] */ public function findByPeriod(PayrollPeriodId $periodId): array;
}
