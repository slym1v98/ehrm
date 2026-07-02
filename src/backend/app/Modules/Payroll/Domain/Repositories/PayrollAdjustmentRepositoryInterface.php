<?php
namespace App\Modules\Payroll\Domain\Repositories;
use App\Modules\Payroll\Domain\Aggregates\PayrollAdjustment\{PayrollAdjustment, PayrollAdjustmentId};
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
interface PayrollAdjustmentRepositoryInterface
{
    public function save(PayrollAdjustment $adjustment): void;
    public function findById(PayrollAdjustmentId $id): ?PayrollAdjustment;
    /** @return PayrollAdjustment[] */ public function findByEntry(PayrollEntryId $entryId): array;
}
