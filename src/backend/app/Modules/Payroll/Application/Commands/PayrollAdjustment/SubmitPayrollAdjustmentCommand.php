<?php
namespace App\Modules\Payroll\Application\Commands\PayrollAdjustment;
readonly class SubmitPayrollAdjustmentCommand
{
    public function __construct(
        public string $entryId,
        public ?string $componentId,
        public string $adjustmentType,
        public float $amount,
        public string $reason,
        public string $submittedBy,
    ) {}
}
