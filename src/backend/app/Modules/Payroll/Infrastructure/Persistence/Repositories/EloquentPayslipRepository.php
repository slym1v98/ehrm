<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Repositories;

use App\Modules\Payroll\Domain\Aggregates\Payslip\{Payslip, PayslipId};
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
use App\Modules\Payroll\Domain\Repositories\PayslipRepositoryInterface;
use App\Modules\Payroll\Domain\ValueObjects\{Money, PayslipStatus};
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayslipModel;
use DateTimeImmutable;
use ReflectionClass;

class EloquentPayslipRepository implements PayslipRepositoryInterface
{
    public function save(Payslip $p): void
    {
        PayslipModel::updateOrCreate(
            ['id' => $p->getId()->value],
            [
                'entry_id' => $p->getEntryId(),
                'employee_id' => $p->getEmployeeId(),
                'period_id' => $p->getPeriodId()->value,
                'gross' => $p->getGross()->toDecimal(),
                'deductions' => $p->getDeductions()->toDecimal(),
                'net' => $p->getNet()->toDecimal(),
                'payload' => $p->getPayload(),
                'status' => $p->getStatus()->value,
                'published_at' => $p->getPublishedAt()?->format('Y-m-d H:i:s'),
                'first_accessed_at' => $p->getFirstAccessedAt()?->format('Y-m-d H:i:s'),
                'access_count' => $p->getAccessCount(),
            ]
        );
    }

    public function findById(PayslipId $id): ?Payslip
    {
        $m = PayslipModel::find($id->value);
        return $m ? $this->toAggregate($m) : null;
    }

    public function findByPeriod(PayrollPeriodId $periodId): array
    {
        return PayslipModel::where('period_id', $periodId->value)->get()
            ->map(fn($m) => $this->toAggregate($m))->all();
    }

    public function findByEmployee(string $employeeId): array
    {
        return PayslipModel::where('employee_id', $employeeId)->get()
            ->map(fn($m) => $this->toAggregate($m))->all();
    }

    private function toAggregate(PayslipModel $m): Payslip
    {
        $ref = new ReflectionClass(Payslip::class);
        $p = $ref->newInstanceWithoutConstructor();
        $props = [
            'id' => PayslipId::fromString($m->id),
            'entryId' => $m->entry_id,
            'employeeId' => $m->employee_id,
            'periodId' => PayrollPeriodId::fromString($m->period_id),
            'gross' => Money::fromDecimal((float)$m->gross),
            'deductions' => Money::fromDecimal((float)$m->deductions),
            'net' => Money::fromDecimal((float)$m->net),
            'payload' => $m->payload ?? [],
            'status' => PayslipStatus::from($m->status),
            'publishedAt' => $m->published_at ? new DateTimeImmutable($m->published_at->format('Y-m-d H:i:s')) : null,
            'firstAccessedAt' => $m->first_accessed_at ? new DateTimeImmutable($m->first_accessed_at->format('Y-m-d H:i:s')) : null,
            'accessCount' => (int)$m->access_count,
        ];
        foreach ($props as $n => $v) $ref->getProperty($n)->setValue($p, $v);
        return $p;
    }
}
