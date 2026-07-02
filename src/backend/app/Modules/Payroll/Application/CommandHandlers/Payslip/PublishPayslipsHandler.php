<?php

namespace App\Modules\Payroll\Application\CommandHandlers\Payslip;

use App\Modules\Payroll\Application\Commands\Payslip\PublishPayslipsCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Aggregates\Payslip\{Payslip, PayslipId};
use App\Modules\Payroll\Domain\Repositories\{PayrollPeriodRepositoryInterface, PayrollEntryRepositoryInterface, PayslipRepositoryInterface};
use App\Modules\Payroll\Domain\Exceptions\{PayrollPeriodNotFoundException, PayrollAlreadyPublishedException};

readonly class PublishPayslipsHandler
{
    public function __construct(
        private PayrollPeriodRepositoryInterface $periodRepo,
        private PayrollEntryRepositoryInterface $entryRepo,
        private PayslipRepositoryInterface $payslipRepo,
    ) {}

    public function handle(PublishPayslipsCommand $command): void
    {
        $periodId = PayrollPeriodId::fromString($command->periodId);
        $period = $this->periodRepo->findById($periodId);
        if ($period === null) throw PayrollPeriodNotFoundException::default();

        // Check not already published
        $existingPayslips = $this->payslipRepo->findByPeriod($periodId);
        if (!empty($existingPayslips)) {
            throw PayrollAlreadyPublishedException::default();
        }

        // Publish
        $entries = $this->entryRepo->findByPeriod($periodId);
        foreach ($entries as $entry) {
            if ($entry->getStatus() === 'error') continue;
            $payslip = Payslip::publishFromEntry(PayslipId::generate(), $entry);
            $this->payslipRepo->save($payslip);
        }

        $event = $period->publish($command->publishedBy);
        $this->periodRepo->save($period);
    }
}
