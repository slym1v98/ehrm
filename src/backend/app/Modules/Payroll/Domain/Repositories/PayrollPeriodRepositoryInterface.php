<?php
namespace App\Modules\Payroll\Domain\Repositories;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\{PayrollPeriod, PayrollPeriodId};
interface PayrollPeriodRepositoryInterface
{
    public function save(PayrollPeriod $period): void;
    public function findById(PayrollPeriodId $id): ?PayrollPeriod;
    public function findByCode(string $periodCode): ?PayrollPeriod;
    /** @return PayrollPeriod[] */ public function findAll(array $filters = []): array;
}
