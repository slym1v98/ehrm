<?php

namespace App\Modules\Payroll\Domain\Services;

use App\Modules\Payroll\Domain\Aggregates\PayrollComponent\PayrollComponent;
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntry;
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Aggregates\PayrollRun\PayrollRunId;
use App\Modules\Payroll\Domain\Ports\AttendanceReadPort;
use App\Modules\Payroll\Domain\Ports\LeaveReadPort;
use App\Modules\Payroll\Domain\Ports\EmployeeContractReadPort;
use DateTimeImmutable;

class PayrollCalculator
{
    public function __construct(
        private PayrollFormulaEngine $formulaEngine,
        private AttendanceReadPort $attendancePort,
        private LeaveReadPort $leavePort,
        private EmployeeContractReadPort $contractPort,
    ) {}

    /**
     * @param string[] $employeeIds
     * @param PayrollComponent[] $components
     * @return PayrollEntry[]
     */
    public function calculateForPeriod(
        array $employeeIds,
        array $components,
        PayrollPeriodId $periodId,
        PayrollRunId $runId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
    ): array {
        $entries = [];
        foreach ($employeeIds as $employeeId) {
            try {
                $contract = $this->contractPort->getContractForEmployee($employeeId, $endDate);
                if ($contract === null) {
                    $entries[] = PayrollEntry::createFailed(
                        PayrollEntryId::generate(), $runId, $periodId, $employeeId,
                        'No active contract found.'
                    );
                    continue;
                }
                $attendance = $this->attendancePort->getAttendanceForEmployee($employeeId, $startDate, $endDate);
                $leave = $this->leavePort->getLeaveForEmployee($employeeId, $startDate, $endDate);

                $result = $this->formulaEngine->calculate(
                    $components,
                    (float) $contract['base_salary'],
                    $attendance,
                    $leave,
                );

                $entries[] = PayrollEntry::create(
                    PayrollEntryId::generate(), $runId, $periodId, $employeeId,
                    $contract, $attendance, $leave, $result,
                );
            } catch (\Throwable $e) {
                $entries[] = PayrollEntry::createFailed(
                    PayrollEntryId::generate(), $runId, $periodId, $employeeId,
                    $e->getMessage(),
                );
            }
        }
        return $entries;
    }
}
