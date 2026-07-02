<?php

namespace App\Modules\Reporting\Application\CommandHandlers;

use App\Modules\Reporting\Application\Commands\ExecuteReportCommand;
use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRun;
use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRunId;
use App\Modules\Reporting\Domain\Exceptions\ReportDefinitionNotFoundException;
use App\Modules\Reporting\Domain\Repositories\ReportDefinitionRepositoryInterface;
use App\Modules\Reporting\Domain\Repositories\ReportRunRepositoryInterface;


class ExecuteReportHandler
{
    private function dispatchJob(string $runId): void
    {
        try {
            \App\Modules\Reporting\Infrastructure\Jobs\ReportRunJob::dispatch($runId);
        } catch (\Throwable) {}
    }
    public function __construct(private ReportDefinitionRepositoryInterface $definitions, private ReportRunRepositoryInterface $runs) {}

    public function handle(ExecuteReportCommand $command): ReportRun
    {
        $definition = $this->definitions->findByCode($command->code);
        if (!$definition) throw new ReportDefinitionNotFoundException($command->code);
        if (!$definition->isActive()) throw new \InvalidArgumentException('Report definition is inactive');

        $run = ReportRun::request(ReportRunId::generate(), (string) $definition->getId(), $command->requestedBy, $command->filters);
        $this->runs->save($run);

        $this->dispatchJob((string) $run->getId());

        return $run;
    }
}
