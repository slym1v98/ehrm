<?php

namespace App\Modules\Reporting\Domain\Repositories;

use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRun;
use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRunId;

interface ReportRunRepositoryInterface
{
    public function findById(ReportRunId $id): ?ReportRun;
    /** @return ReportRun[] */
    public function listByUser(string $userId): array;
    /** @return ReportRun[] */
    public function listAll(): array;
    public function save(ReportRun $run): void;
}
