<?php

namespace App\Modules\Reporting\Infrastructure\Jobs;

use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinitionId;
use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRunId;
use App\Modules\Reporting\Domain\Repositories\ReportDefinitionRepositoryInterface;
use App\Modules\Reporting\Domain\Repositories\ReportRunRepositoryInterface;
use App\Modules\Reporting\Infrastructure\Console\ReportQueryRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ReportRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 1;

    public function __construct(public string $runId) {}

    public function handle(ReportRunRepositoryInterface $runs, ReportDefinitionRepositoryInterface $defs, ReportQueryRegistry $registry): void
    {
        $run = $runs->findById(new ReportRunId($this->runId));
        if (!$run) return;
        $now = CarbonImmutable::now();
        try {
            if ($run->getStatus()->value === 'requested') {
                $run->start($now);
                $runs->save($run);
            }
            $definition = $defs->findById(new ReportDefinitionId($run->getReportDefinitionId()));
            if (!$definition) throw new \RuntimeException('Report definition not found');
            $query = $registry->resolve($definition->getQueryClass());
            $result = $query->execute($run->getFilters(), $run->getRequestedBy());
            $run->complete($result, CarbonImmutable::now());
        } catch (Throwable $e) {
            $run->fail($e->getMessage(), CarbonImmutable::now());
        }
        $runs->save($run);
    }
}
