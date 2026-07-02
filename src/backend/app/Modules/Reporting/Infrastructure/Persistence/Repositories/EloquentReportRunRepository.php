<?php

namespace App\Modules\Reporting\Infrastructure\Persistence\Repositories;

use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRun;
use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRunId;
use App\Modules\Reporting\Domain\Repositories\ReportRunRepositoryInterface;
use App\Modules\Reporting\Domain\ValueObjects\ReportRunStatus;
use App\Modules\Reporting\Infrastructure\Persistence\Eloquent\ReportRunModel;
use Carbon\CarbonImmutable;

class EloquentReportRunRepository implements ReportRunRepositoryInterface
{
    public function __construct(private ReportRunModel $model) {}

    public function findById(ReportRunId $id): ?ReportRun
    {
        $record = $this->model->find($id->value());
        return $record ? self::toDomain($record) : null;
    }

    public function listByUser(string $userId): array
    {
        return $this->model->where('requested_by', $userId)->orderByDesc('created_at')->get()->map(fn($r) => self::toDomain($r))->all();
    }

    public function listAll(): array
    {
        return $this->model->orderByDesc('created_at')->get()->map(fn($r) => self::toDomain($r))->all();
    }

    public function save(ReportRun $run): void
    {
        $this->model->updateOrCreate(['id' => (string) $run->getId()], [
            'report_definition_id' => $run->getReportDefinitionId(),
            'requested_by' => $run->getRequestedBy(),
            'filters' => $run->getFilters(),
            'status' => $run->getStatus()->value,
            'result' => $run->getResult(),
            'error' => $run->getError(),
            'started_at' => $run->getStartedAt()?->toDateTimeString(),
            'completed_at' => $run->getCompletedAt()?->toDateTimeString(),
        ]);
    }

    public static function toDomain(ReportRunModel $model): ReportRun
    {
        return ReportRun::reconstitute(
            new ReportRunId($model->id),
            $model->report_definition_id,
            $model->requested_by,
            $model->filters ?? [],
            ReportRunStatus::from($model->status),
            $model->result,
            $model->error,
            $model->started_at ? CarbonImmutable::parse($model->started_at) : null,
            $model->completed_at ? CarbonImmutable::parse($model->completed_at) : null,
        );
    }
}
