<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollAdjustment;

use App\Modules\Payroll\Application\Commands\PayrollAdjustment\SubmitPayrollAdjustmentCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollAdjustment\{PayrollAdjustment, PayrollAdjustmentId};
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
use App\Modules\Payroll\Domain\Repositories\{PayrollAdjustmentRepositoryInterface, PayrollEntryRepositoryInterface};
use App\Modules\Payroll\Domain\Exceptions\{PayrollEntryNotFoundException, DuplicatePendingAdjustmentException};
use App\Modules\Payroll\Domain\ValueObjects\AdjustmentStatus;
use App\Modules\Payroll\Domain\ValueObjects\Money;

readonly class SubmitPayrollAdjustmentHandler
{
    public function __construct(
        private PayrollAdjustmentRepositoryInterface $adjustmentRepo,
        private PayrollEntryRepositoryInterface $entryRepo,
    ) {}

    public function handle(SubmitPayrollAdjustmentCommand $command): PayrollAdjustment
    {
        $entryId = PayrollEntryId::fromString($command->entryId);
        $entry = $this->entryRepo->findById($entryId);
        if ($entry === null) throw PayrollEntryNotFoundException::default();

        // Check no pending adjustment exists
        $existing = $this->adjustmentRepo->findByEntry($entryId);
        foreach ($existing as $adj) {
            if ($adj->getStatus() === AdjustmentStatus::Pending) {
                throw DuplicatePendingAdjustmentException::default();
            }
        }

        $adjustment = PayrollAdjustment::submit(
            PayrollAdjustmentId::generate(),
            $entryId,
            $command->componentId,
            $command->adjustmentType,
            Money::fromDecimal($command->amount),
            $command->reason,
            $command->submittedBy,
        );

        $this->adjustmentRepo->save($adjustment);
        return $adjustment;
    }
}
