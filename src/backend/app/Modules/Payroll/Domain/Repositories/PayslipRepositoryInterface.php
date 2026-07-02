<?php
namespace App\Modules\Payroll\Domain\Repositories;
use App\Modules\Payroll\Domain\Aggregates\Payslip\{Payslip, PayslipId};
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
interface PayslipRepositoryInterface
{
    public function save(Payslip $payslip): void;
    public function findById(PayslipId $id): ?Payslip;
    /** @return Payslip[] */ public function findByPeriod(PayrollPeriodId $periodId): array;
    /** @return Payslip[] */ public function findByEmployee(string $employeeId): array;
}
