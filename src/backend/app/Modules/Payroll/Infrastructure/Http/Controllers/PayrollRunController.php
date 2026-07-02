<?php

namespace App\Modules\Payroll\Infrastructure\Http\Controllers;

use App\Modules\Payroll\Application\Commands\PayrollRun\StartPayrollRunCommand;
use App\Modules\Payroll\Application\Commands\PayrollRun\CompletePayrollRunCommand;
use App\Modules\Payroll\Application\CommandHandlers\PayrollRun\StartPayrollRunHandler;
use App\Modules\Payroll\Application\CommandHandlers\PayrollRun\CompletePayrollRunHandler;
use App\Modules\Payroll\Infrastructure\Http\Resources\PayrollRunResource;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollRunModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollRunController
{
    public function __construct(
        private StartPayrollRunHandler $startHandler,
        private CompletePayrollRunHandler $completeHandler,
    ) {}

    public function start(Request $request, string $periodId): JsonResponse
    {
        $run = $this->startHandler->handle(new StartPayrollRunCommand($periodId, (string)$request->user()->id));
        // Execute synchronously for MVP (Phase 2 upgrade: queue job)
        $this->completeHandler->handle(new CompletePayrollRunCommand($run->getId()->value, $periodId));
        $model = PayrollRunModel::findOrFail($run->getId()->value);
        return response()->json(['data' => new PayrollRunResource($model)], 201);
    }
}
