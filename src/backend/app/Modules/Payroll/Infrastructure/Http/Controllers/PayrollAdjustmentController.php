<?php

namespace App\Modules\Payroll\Infrastructure\Http\Controllers;

use App\Modules\Payroll\Application\Commands\PayrollAdjustment\SubmitPayrollAdjustmentCommand;
use App\Modules\Payroll\Application\Commands\PayrollAdjustment\ApprovePayrollAdjustmentCommand;
use App\Modules\Payroll\Application\Commands\PayrollAdjustment\RejectPayrollAdjustmentCommand;
use App\Modules\Payroll\Application\CommandHandlers\PayrollAdjustment\SubmitPayrollAdjustmentHandler;
use App\Modules\Payroll\Application\CommandHandlers\PayrollAdjustment\ApprovePayrollAdjustmentHandler;
use App\Modules\Payroll\Application\CommandHandlers\PayrollAdjustment\RejectPayrollAdjustmentHandler;
use App\Modules\Payroll\Infrastructure\Http\Requests\SubmitPayrollAdjustmentRequest;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollAdjustmentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollAdjustmentController
{
    public function __construct(
        private SubmitPayrollAdjustmentHandler $submitHandler,
        private ApprovePayrollAdjustmentHandler $approveHandler,
        private RejectPayrollAdjustmentHandler $rejectHandler,
    ) {}

    public function index(string $entryId): JsonResponse
    {
        return response()->json([
            'data' => PayrollAdjustmentModel::where('entry_id', $entryId)->get(),
        ]);
    }

    public function store(SubmitPayrollAdjustmentRequest $request, string $entryId): JsonResponse
    {
        $adj = $this->submitHandler->handle(new SubmitPayrollAdjustmentCommand(
            entryId: $entryId,
            componentId: $request->input('component_id'),
            adjustmentType: $request->input('adjustment_type'),
            amount: (float)$request->input('amount'),
            reason: $request->input('reason'),
            submittedBy: (string)$request->user()->id,
        ));
        return response()->json(['data' => PayrollAdjustmentModel::findOrFail($adj->getId()->value)], 201);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $this->approveHandler->handle(new ApprovePayrollAdjustmentCommand($id, (string)$request->user()->id));
        return response()->json(['message' => 'Adjustment approved']);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $reason = (string)$request->input('reason', 'Rejected');
        $this->rejectHandler->handle(new RejectPayrollAdjustmentCommand($id, (string)$request->user()->id, $reason));
        return response()->json(['message' => 'Adjustment rejected']);
    }
}
