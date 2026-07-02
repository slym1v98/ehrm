<?php
namespace App\Modules\Payroll\Application\Commands\Payslip;
readonly class PublishPayslipsCommand
{
    public function __construct(public string $periodId, public string $publishedBy) {}
}
