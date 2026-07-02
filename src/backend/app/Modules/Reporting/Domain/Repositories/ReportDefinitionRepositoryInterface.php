<?php

namespace App\Modules\Reporting\Domain\Repositories;

use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinition;
use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinitionId;

interface ReportDefinitionRepositoryInterface
{
    public function findById(ReportDefinitionId $id): ?ReportDefinition;
    public function findByCode(string $code): ?ReportDefinition;
    /** @return ReportDefinition[] */
    public function list(): array;
    public function save(ReportDefinition $definition): void;
}
