<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollAdjustment;

use App\Modules\Payroll\Application\Commands\PayrollAdjustment\RejectPayrollAdjustmentCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollAdjustment\PayrollAdjustmentId;
use App\Modules\Payroll\Domain\Repositories\PayrollAdjustmentRepositoryInterface;
use App\Modules\Payroll\Domain\Exceptions\PayrollAdjustmentNotFoundException;

readonly class RejectPayrollAdjustmentHandler
{
    public function __construct(private PayrollAdjustmentRepositoryInterface $adjustmentRepo) {}

    public function handle(RejectPayrollAdjustmentCommand $command): void
    {
        $id = PayrollAdjustmentId::fromString($command->adjustmentId);
        $adjustment = $this->adjustmentRepo->findById($id);
        if ($adjustment === null) throw PayrollAdjustmentNotFoundException::default();

        $adjustment->reject($command->rejectedBy, $command->reason);
        $this->adjustmentRepo->save($adjustment);
    }
}
