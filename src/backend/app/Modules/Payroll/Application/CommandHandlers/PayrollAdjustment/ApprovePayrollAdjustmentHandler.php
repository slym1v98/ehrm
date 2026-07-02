<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollAdjustment;

use App\Modules\Payroll\Application\Commands\PayrollAdjustment\ApprovePayrollAdjustmentCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollAdjustment\PayrollAdjustmentId;
use App\Modules\Payroll\Domain\Repositories\{PayrollAdjustmentRepositoryInterface, PayrollEntryRepositoryInterface};
use App\Modules\Payroll\Domain\Exceptions\PayrollAdjustmentNotFoundException;

readonly class ApprovePayrollAdjustmentHandler
{
    public function __construct(
        private PayrollAdjustmentRepositoryInterface $adjustmentRepo,
        private PayrollEntryRepositoryInterface $entryRepo,
    ) {}

    public function handle(ApprovePayrollAdjustmentCommand $command): void
    {
        $id = PayrollAdjustmentId::fromString($command->adjustmentId);
        $adjustment = $this->adjustmentRepo->findById($id);
        if ($adjustment === null) throw PayrollAdjustmentNotFoundException::default();

        $event = $adjustment->approve($command->approvedBy);
        $this->adjustmentRepo->save($adjustment);

        // Apply delta to entry
        $entry = $this->entryRepo->findById($adjustment->getEntryId());
        if ($entry !== null) {
            $entry->applyAdjustment($adjustment->getDelta());
            $this->entryRepo->save($entry);
        }
    }
}
