<?php

namespace App\Modules\Reporting\Infrastructure\Http\Controllers;

use App\Modules\Reporting\Application\CommandHandlers\ExecuteReportHandler;
use App\Modules\Reporting\Application\Commands\ExecuteReportCommand;
use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRunId;
use App\Modules\Reporting\Domain\Exceptions\ReportRunNotFoundException;
use App\Modules\Reporting\Domain\Repositories\ReportDefinitionRepositoryInterface;
use App\Modules\Reporting\Domain\Repositories\ReportRunRepositoryInterface;
use App\Modules\Reporting\Infrastructure\Http\Resources\ReportDefinitionResource;
use App\Modules\Reporting\Infrastructure\Http\Resources\ReportRunResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController
{
    public function __construct(
        private ReportDefinitionRepositoryInterface $definitions,
        private ReportRunRepositoryInterface $runs,
        private ExecuteReportHandler $executeHandler,
    ) {}

    public function definitions(): JsonResponse
    {
        return response()->json(['data' => array_map(fn($d) => new ReportDefinitionResource($d), $this->definitions->list())]);
    }

    public function run(Request $request, string $code): JsonResponse
    {
        $filters = $request->input('filters', []);
        $run = $this->executeHandler->handle(new ExecuteReportCommand($code, $request->user()->id, $filters));
        return response()->json(['data' => new ReportRunResource($run)], 201);
    }

    public function listRuns(Request $request): JsonResponse
    {
        $list = $request->user()->can('report.run.view-all') ? $this->runs->listAll() : $this->runs->listByUser($request->user()->id);
        return response()->json(['data' => array_map(fn($r) => new ReportRunResource($r), $list)]);
    }

    public function showRun(Request $request, string $id): JsonResponse
    {
        $run = $this->runs->findById(new ReportRunId($id));
        if (!$run) throw new ReportRunNotFoundException($id);
        if (!$request->user()->can('report.run.view-all') && $run->getRequestedBy() !== $request->user()->id) {
            throw new ReportRunNotFoundException($id);
        }
        return response()->json(['data' => new ReportRunResource($run)]);
    }
}
