<?php

namespace App\Modules\Reporting\Infrastructure\Persistence\Repositories;

use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinition;
use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinitionId;
use App\Modules\Reporting\Domain\Repositories\ReportDefinitionRepositoryInterface;
use App\Modules\Reporting\Infrastructure\Persistence\Eloquent\ReportDefinitionModel;

class EloquentReportDefinitionRepository implements ReportDefinitionRepositoryInterface
{
    public function __construct(private ReportDefinitionModel $model) {}

    public function findById(ReportDefinitionId $id): ?ReportDefinition
    {
        $record = $this->model->find($id->value());
        return $record ? self::toDomain($record) : null;
    }

    public function findByCode(string $code): ?ReportDefinition
    {
        $record = $this->model->where('code', $code)->first();
        return $record ? self::toDomain($record) : null;
    }

    public function list(): array
    {
        return $this->model->orderBy('name')->get()->map(fn($r) => self::toDomain($r))->all();
    }

    public function save(ReportDefinition $definition): void
    {
        $this->model->updateOrCreate(['id' => (string) $definition->getId()], [
            'code' => $definition->getCode(),
            'name' => $definition->getName(),
            'description' => $definition->getDescription(),
            'query_class' => $definition->getQueryClass(),
            'filters_schema' => $definition->getFiltersSchema(),
            'columns_schema' => $definition->getColumnsSchema(),
            'is_active' => $definition->isActive(),
        ]);
    }

    public static function toDomain(ReportDefinitionModel $model): ReportDefinition
    {
        return ReportDefinition::create(
            new ReportDefinitionId($model->id),
            $model->code,
            $model->name,
            $model->description,
            $model->query_class,
            $model->filters_schema ?? [],
            $model->columns_schema ?? [],
            $model->is_active,
        );
    }
}
