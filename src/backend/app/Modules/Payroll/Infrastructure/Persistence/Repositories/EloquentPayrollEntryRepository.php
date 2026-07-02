<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Repositories;

use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\{PayrollEntry, PayrollEntryId};
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Aggregates\PayrollRun\PayrollRunId;
use App\Modules\Payroll\Domain\Repositories\PayrollEntryRepositoryInterface;
use App\Modules\Payroll\Domain\ValueObjects\Money;
use App\Modules\Payroll\Domain\ValueObjects\PayrollFormulaResult;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\{PayrollEntryModel, PayrollEntryLineModel};
use DateTimeImmutable;
use ReflectionClass;

class EloquentPayrollEntryRepository implements PayrollEntryRepositoryInterface
{
    public function save(PayrollEntry $entry): void
    {
        PayrollEntryModel::updateOrCreate(
            ['id' => $entry->getId()->value],
            [
                'run_id' => $entry->getRunId()->value,
                'period_id' => $entry->getPeriodId()->value,
                'employee_id' => $entry->getEmployeeId(),
                'contract_snapshot' => $entry->getContractSnapshot(),
                'attendance_snapshot' => $entry->getAttendanceSnapshot(),
                'leave_snapshot' => $entry->getLeaveSnapshot(),
                'gross_amount' => $entry->getGrossAmount()->toDecimal(),
                'deduction_amount' => $entry->getDeductionAmount()->toDecimal(),
                'net_amount' => $entry->getNetAmount()->toDecimal(),
                'status' => $entry->getStatus(),
                'error_message' => $entry->getErrorMessage(),
                'reviewed_by' => $entry->getReviewedBy(),
                'reviewed_at' => $entry->getReviewedAt()?->format('Y-m-d H:i:s'),
            ]
        );
        // Save lines
        $model = PayrollEntryModel::find($entry->getId()->value);
        if ($model) {
            $model->lines()->delete();
            foreach ($entry->getLines() as $line) {
                $amount = $line['amount'];
                PayrollEntryLineModel::create([
                    'entry_id' => $entry->getId()->value,
                    'component_id' => $line['component_id'],
                    'category' => $line['category'],
                    'amount' => $amount instanceof Money ? $amount->toDecimal() : (float)$amount,
                    'calculation_note' => $line['note'] ?? null,
                ]);
            }
        }
    }

    public function findById(PayrollEntryId $id): ?PayrollEntry
    {
        $m = PayrollEntryModel::with('lines')->find($id->value);
        return $m ? $this->toAggregate($m) : null;
    }

    public function findByPeriod(PayrollPeriodId $periodId): array
    {
        return PayrollEntryModel::with('lines')
            ->where('period_id', $periodId->value)
            ->get()->map(fn($m) => $this->toAggregate($m))->all();
    }

    private function toAggregate(PayrollEntryModel $m): PayrollEntry
    {
        $ref = new ReflectionClass(PayrollEntry::class);
        $e = $ref->newInstanceWithoutConstructor();
        $lines = $m->relationLoaded('lines') && $m->lines
            ? $m->lines->map(fn($l) => [
                'component_id' => $l->component_id,
                'category' => $l->category,
                'amount' => Money::fromDecimal((float)$l->amount),
                'note' => $l->calculation_note,
            ])->all()
            : [];
        $props = [
            'id' => PayrollEntryId::fromString($m->id),
            'runId' => PayrollRunId::fromString($m->run_id),
            'periodId' => PayrollPeriodId::fromString($m->period_id),
            'employeeId' => $m->employee_id,
            'contractSnapshot' => $m->contract_snapshot ?? [],
            'attendanceSnapshot' => $m->attendance_snapshot ?? [],
            'leaveSnapshot' => $m->leave_snapshot ?? [],
            'grossAmount' => Money::fromDecimal((float)$m->gross_amount),
            'deductionAmount' => Money::fromDecimal((float)$m->deduction_amount),
            'netAmount' => Money::fromDecimal((float)$m->net_amount),
            'lines' => $lines,
            'status' => $m->status,
            'errorMessage' => $m->error_message,
            'reviewedBy' => $m->reviewed_by,
            'reviewedAt' => $m->reviewed_at ? new DateTimeImmutable($m->reviewed_at->format('Y-m-d H:i:s')) : null,
        ];
        foreach ($props as $n => $v) $ref->getProperty($n)->setValue($e, $v);
        return $e;
    }
}
