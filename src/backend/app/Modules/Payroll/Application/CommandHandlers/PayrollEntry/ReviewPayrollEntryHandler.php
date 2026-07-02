<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollEntry;

use App\Modules\Payroll\Application\Commands\PayrollEntry\ReviewPayrollEntryCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
use App\Modules\Payroll\Domain\Repositories\PayrollEntryRepositoryInterface;
use App\Modules\Payroll\Domain\Exceptions\PayrollEntryNotFoundException;

readonly class ReviewPayrollEntryHandler
{
    public function __construct(private PayrollEntryRepositoryInterface $entryRepo) {}

    public function handle(ReviewPayrollEntryCommand $command): void
    {
        $id = PayrollEntryId::fromString($command->entryId);
        $entry = $this->entryRepo->findById($id);
        if ($entry === null) throw PayrollEntryNotFoundException::default();

        $entry->review($command->reviewedBy);
        $this->entryRepo->save($entry);
    }
}
