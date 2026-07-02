<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Repositories;

use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\{PayrollPeriod, PayrollPeriodId};
use App\Modules\Payroll\Domain\Repositories\PayrollPeriodRepositoryInterface;
use App\Modules\Payroll\Domain\ValueObjects\PeriodStatus;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollPeriodModel;
use DateTimeImmutable;
use ReflectionClass;

class EloquentPayrollPeriodRepository implements PayrollPeriodRepositoryInterface
{
    public function save(PayrollPeriod $period): void
    {
        PayrollPeriodModel::updateOrCreate(
            ['id' => $period->getId()->value],
            [
                'period_code' => $period->getPeriodCode(),
                'start_date' => $period->getStartDate()->format('Y-m-d'),
                'end_date' => $period->getEndDate()->format('Y-m-d'),
                'cutoff_date' => $period->getCutoffDate()->format('Y-m-d'),
                'status' => $period->getStatus()->value,
                'attendance_period_id' => $period->getAttendancePeriodId(),
                'workflow_request_id' => $period->getWorkflowRequestId(),
                'opened_by' => $period->getOpenedBy(),
                'opened_at' => $period->getOpenedAt()->format('Y-m-d H:i:s'),
                'approved_by' => $period->getApprovedBy(),
                'approved_at' => $period->getApprovedAt()?->format('Y-m-d H:i:s'),
                'locked_by' => $period->getLockedBy(),
                'locked_at' => $period->getLockedAt()?->format('Y-m-d H:i:s'),
                'published_at' => $period->getPublishedAt()?->format('Y-m-d H:i:s'),
            ]
        );
        foreach ($period->getRecordedEvents() as $event) {
            event($event);
        }
        $period->clearRecordedEvents();
    }

    public function findById(PayrollPeriodId $id): ?PayrollPeriod
    {
        $model = PayrollPeriodModel::find($id->value);
        return $model ? $this->toAggregate($model) : null;
    }

    public function findByCode(string $periodCode): ?PayrollPeriod
    {
        $model = PayrollPeriodModel::where('period_code', $periodCode)->first();
        return $model ? $this->toAggregate($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = PayrollPeriodModel::query();
        if (isset($filters['status'])) $query->where('status', $filters['status']);
        if (isset($filters['from'])) $query->where('start_date', '>=', $filters['from']);
        if (isset($filters['to'])) $query->where('end_date', '<=', $filters['to']);
        return $query->orderBy('start_date', 'desc')->get()->map(fn($m) => $this->toAggregate($m))->all();
    }

    private function toAggregate(PayrollPeriodModel $model): PayrollPeriod
    {
        $ref = new ReflectionClass(PayrollPeriod::class);
        $period = $ref->newInstanceWithoutConstructor();
        $props = [
            'id' => PayrollPeriodId::fromString($model->id),
            'periodCode' => $model->period_code,
            'startDate' => new DateTimeImmutable($model->start_date->format('Y-m-d')),
            'endDate' => new DateTimeImmutable($model->end_date->format('Y-m-d')),
            'cutoffDate' => new DateTimeImmutable($model->cutoff_date->format('Y-m-d')),
            'status' => PeriodStatus::from($model->status),
            'attendancePeriodId' => $model->attendance_period_id,
            'workflowRequestId' => $model->workflow_request_id,
            'openedBy' => $model->opened_by,
            'openedAt' => new DateTimeImmutable($model->opened_at?->format('Y-m-d H:i:s') ?? 'now'),
            'approvedBy' => $model->approved_by,
            'approvedAt' => $model->approved_at ? new DateTimeImmutable($model->approved_at->format('Y-m-d H:i:s')) : null,
            'lockedBy' => $model->locked_by,
            'lockedAt' => $model->locked_at ? new DateTimeImmutable($model->locked_at->format('Y-m-d H:i:s')) : null,
            'publishedAt' => $model->published_at ? new DateTimeImmutable($model->published_at->format('Y-m-d H:i:s')) : null,
        ];
        foreach ($props as $name => $val) {
            $prop = $ref->getProperty($name);
            $prop->setValue($period, $val);
        }
        $eventsProp = $ref->getProperty('recordedEvents');
        $eventsProp->setValue($period, []);
        return $period;
    }
}
