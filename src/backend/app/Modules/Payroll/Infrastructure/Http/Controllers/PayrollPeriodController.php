<?php

namespace App\Modules\Payroll\Infrastructure\Http\Controllers;

use App\Modules\Payroll\Application\Commands\PayrollPeriod\OpenPayrollPeriodCommand;
use App\Modules\Payroll\Application\Commands\PayrollPeriod\ClosePayrollPeriodCommand;
use App\Modules\Payroll\Application\CommandHandlers\PayrollPeriod\OpenPayrollPeriodHandler;
use App\Modules\Payroll\Application\CommandHandlers\PayrollPeriod\ClosePayrollPeriodHandler;
use App\Modules\Payroll\Application\CommandHandlers\PayrollPeriod\ReopenPayrollPeriodHandler;
use App\Modules\Payroll\Application\Commands\PayrollPeriod\ReopenPayrollPeriodCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Repositories\PayrollPeriodRepositoryInterface;
use App\Modules\Payroll\Infrastructure\Http\Requests\StorePayrollPeriodRequest;
use App\Modules\Payroll\Infrastructure\Http\Resources\PayrollPeriodResource;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollPeriodModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollPeriodController
{
    public function __construct(
        private OpenPayrollPeriodHandler $openHandler,
        private ClosePayrollPeriodHandler $closeHandler,
        private ReopenPayrollPeriodHandler $reopenHandler,
        private PayrollPeriodRepositoryInterface $periodRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = PayrollPeriodModel::query()->orderByDesc('start_date')
            ->paginate((int)$request->input('per_page', 20), ['*'], 'page', (int)$request->input('page', 1));
        return response()->json(new PaginatedCollection($paginator->through(fn($m) => new PayrollPeriodResource($m))));
    }

    public function show(string $id): JsonResponse
    {
        $model = PayrollPeriodModel::findOrFail($id);
        return response()->json(['data' => new PayrollPeriodResource($model)]);
    }

    public function store(StorePayrollPeriodRequest $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $period = $this->openHandler->handle(new OpenPayrollPeriodCommand(
            periodCode: $request->input('period_code'),
            startDate: new DateTimeImmutable($request->input('start_date')),
            endDate: new DateTimeImmutable($request->input('end_date')),
            cutoffDate: new DateTimeImmutable($request->input('cutoff_date')),
            attendancePeriodId: $request->input('attendance_period_id'),
            openedBy: $userId,
        ));
        $model = PayrollPeriodModel::findOrFail($period->getId()->value);
        return response()->json(['data' => new PayrollPeriodResource($model)], 201);
    }

    public function submitApproval(Request $request, string $id): JsonResponse
    {
        $period = $this->periodRepo->findById(PayrollPeriodId::fromString($id));
        if (!$period) return response()->json(['message' => 'Period not found'], 404);
        $workflowRequestId = $request->input('workflow_request_id', $id);
        $period->submitForApproval((string)$workflowRequestId);
        $this->periodRepo->save($period);
        return response()->json(['message' => 'Submitted for approval']);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $period = $this->periodRepo->findById(PayrollPeriodId::fromString($id));
        if (!$period) return response()->json(['message' => 'Period not found'], 404);
        $period->approve((string)$request->user()->id);
        $this->periodRepo->save($period);
        return response()->json(['message' => 'Payroll approved']);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $period = $this->periodRepo->findById(PayrollPeriodId::fromString($id));
        if (!$period) return response()->json(['message' => 'Period not found'], 404);
        $period->reject();
        $this->periodRepo->save($period);
        return response()->json(['message' => 'Payroll rejected']);
    }

    public function lock(Request $request, string $id): JsonResponse
    {
        $period = $this->periodRepo->findById(PayrollPeriodId::fromString($id));
        if (!$period) return response()->json(['message' => 'Period not found'], 404);
        $period->lock((string)$request->user()->id);
        $this->periodRepo->save($period);
        return response()->json(['message' => 'Payroll locked']);
    }

    public function reopen(Request $request, string $id): JsonResponse
    {
        $this->reopenHandler->handle(new ReopenPayrollPeriodCommand($id, (string)$request->user()->id));
        return response()->json(['message' => 'Period reopened']);
    }
}
