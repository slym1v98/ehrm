<?php
namespace App\Modules\Payroll\Domain\Repositories;
use App\Modules\Payroll\Domain\Aggregates\PayrollRun\{PayrollRun, PayrollRunId};
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
interface PayrollRunRepositoryInterface
{
    public function save(PayrollRun $run): void;
    public function findById(PayrollRunId $id): ?PayrollRun;
    /** @return PayrollRun[] */ public function findByPeriod(PayrollPeriodId $periodId): array;
    public function hasRunningRun(PayrollPeriodId $periodId): bool;
}
