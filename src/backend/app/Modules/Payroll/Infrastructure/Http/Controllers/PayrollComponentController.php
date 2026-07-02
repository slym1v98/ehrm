<?php

namespace App\Modules\Payroll\Infrastructure\Http\Controllers;

use App\Modules\Payroll\Domain\Aggregates\PayrollComponent\{PayrollComponent, PayrollComponentId};
use App\Modules\Payroll\Domain\Repositories\PayrollComponentRepositoryInterface;
use App\Modules\Payroll\Domain\ValueObjects\{CalculationType, ComponentCategory, Money};
use App\Modules\Payroll\Infrastructure\Http\Requests\{StorePayrollComponentRequest, UpdatePayrollComponentRequest};
use App\Modules\Payroll\Infrastructure\Http\Resources\PayrollComponentResource;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollComponentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollComponentController
{
    public function __construct(private PayrollComponentRepositoryInterface $repo) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => PayrollComponentModel::orderBy('category')->orderBy('code')->get()->map(fn($m) => (new PayrollComponentResource($m))->toArray(request()))
        ]);
    }

    public function store(StorePayrollComponentRequest $request): JsonResponse
    {
        $component = PayrollComponent::create(
            PayrollComponentId::generate(),
            $request->input('code'),
            $request->input('name'),
            ComponentCategory::from($request->input('category')),
            CalculationType::from($request->input('calculation_type')),
            $request->input('percent_base_component_id'),
            $request->input('default_amount') !== null ? Money::fromDecimal((float)$request->input('default_amount')) : null,
            $request->input('default_percent') !== null ? (float)$request->input('default_percent') : null,
            (bool)$request->input('taxable', true),
        );
        $this->repo->save($component);
        return response()->json(['data' => new PayrollComponentResource(PayrollComponentModel::findOrFail($component->getId()->value))], 201);
    }

    public function update(UpdatePayrollComponentRequest $request, string $id): JsonResponse
    {
        $model = PayrollComponentModel::findOrFail($id);
        $model->fill($request->validated())->save();
        return response()->json(['data' => new PayrollComponentResource($model->fresh())]);
    }

    public function destroy(string $id): JsonResponse
    {
        $model = PayrollComponentModel::findOrFail($id);
        $model->active = false;
        $model->save();
        return response()->json(['message' => 'Component deactivated']);
    }
}
