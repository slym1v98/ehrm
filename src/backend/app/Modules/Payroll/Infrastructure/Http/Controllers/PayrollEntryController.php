<?php

namespace App\Modules\Payroll\Infrastructure\Http\Controllers;

use App\Modules\Payroll\Application\Commands\PayrollEntry\ReviewPayrollEntryCommand;
use App\Modules\Payroll\Application\CommandHandlers\PayrollEntry\ReviewPayrollEntryHandler;
use App\Modules\Payroll\Infrastructure\Http\Resources\PayrollEntryResource;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollEntryModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollEntryController
{
    public function __construct(
        private ReviewPayrollEntryHandler $reviewHandler,
    ) {}

    public function index(Request $request, string $periodId): JsonResponse
    {
        $entries = PayrollEntryModel::with('lines')->where('period_id', $periodId)->paginate(50);
        return response()->json([
            'data' => $entries->map(fn($m) => (new PayrollEntryResource($m))->toArray($request)),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $model = PayrollEntryModel::with('lines')->findOrFail($id);
        return response()->json(['data' => new PayrollEntryResource($model)]);
    }

    public function review(Request $request, string $id): JsonResponse
    {
        $this->reviewHandler->handle(new ReviewPayrollEntryCommand($id, (string)$request->user()->id));
        return response()->json(['message' => 'Entry reviewed']);
    }
}
