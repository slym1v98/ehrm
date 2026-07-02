<?php
namespace App\Modules\Payroll\Domain\Repositories;
use App\Modules\Payroll\Domain\Aggregates\PayrollComponent\{PayrollComponent, PayrollComponentId};
interface PayrollComponentRepositoryInterface
{
    public function save(PayrollComponent $component): void;
    public function findById(PayrollComponentId $id): ?PayrollComponent;
    public function findByCode(string $code): ?PayrollComponent;
    /** @return PayrollComponent[] */ public function findActive(): array;
}
