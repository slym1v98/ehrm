<?php
namespace App\Modules\Payroll\Application\Commands\PayrollEntry;
readonly class ReviewPayrollEntryCommand
{
    public function __construct(public string $entryId, public string $reviewedBy) {}
}
