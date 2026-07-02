<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Repositories;

use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Aggregates\PayrollRun\{PayrollRun, PayrollRunId};
use App\Modules\Payroll\Domain\Repositories\PayrollRunRepositoryInterface;
use App\Modules\Payroll\Domain\ValueObjects\RunStatus;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollRunModel;
use DateTimeImmutable;
use ReflectionClass;

class EloquentPayrollRunRepository implements PayrollRunRepositoryInterface
{
    public function save(PayrollRun $run): void
    {
        PayrollRunModel::updateOrCreate(
            ['id' => $run->getId()->value],
            [
                'period_id' => $run->getPeriodId()->value,
                'run_type' => $run->getRunType(),
                'status' => $run->getStatus()->value,
                'formula_version' => $run->getFormulaVersion(),
                'triggered_by' => $run->getTriggeredBy(),
                'started_at' => $run->getStartedAt()->format('Y-m-d H:i:s'),
                'completed_at' => $run->getCompletedAt()?->format('Y-m-d H:i:s'),
                'error_summary' => $run->getErrorSummary(),
            ]
        );
    }

    public function findById(PayrollRunId $id): ?PayrollRun
    {
        $m = PayrollRunModel::find($id->value);
        return $m ? $this->toAggregate($m) : null;
    }

    public function findByPeriod(PayrollPeriodId $periodId): array
    {
        return PayrollRunModel::where('period_id', $periodId->value)->get()
            ->map(fn($m) => $this->toAggregate($m))->all();
    }

    public function hasRunningRun(PayrollPeriodId $periodId): bool
    {
        return PayrollRunModel::where('period_id', $periodId->value)
            ->where('status', 'running')->exists();
    }

    private function toAggregate(PayrollRunModel $m): PayrollRun
    {
        $ref = new ReflectionClass(PayrollRun::class);
        $r = $ref->newInstanceWithoutConstructor();
        $props = [
            'id' => PayrollRunId::fromString($m->id),
            'periodId' => PayrollPeriodId::fromString($m->period_id),
            'runType' => $m->run_type,
            'status' => RunStatus::from($m->status),
            'formulaVersion' => $m->formula_version,
            'triggeredBy' => $m->triggered_by,
            'startedAt' => new DateTimeImmutable($m->started_at->format('Y-m-d H:i:s')),
            'completedAt' => $m->completed_at ? new DateTimeImmutable($m->completed_at->format('Y-m-d H:i:s')) : null,
            'errorSummary' => $m->error_summary,
        ];
        foreach ($props as $n => $v) $ref->getProperty($n)->setValue($r, $v);
        return $r;
    }
}
