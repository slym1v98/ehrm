<?php

namespace App\Modules\Shift\Infrastructure\Http\Controllers;

use App\Modules\Shift\Application\CommandHandlers\ShiftTemplate\ActivateShiftTemplateHandler;
use App\Modules\Shift\Application\CommandHandlers\ShiftTemplate\CreateShiftTemplateHandler;
use App\Modules\Shift\Application\CommandHandlers\ShiftTemplate\DeactivateShiftTemplateHandler;
use App\Modules\Shift\Application\CommandHandlers\ShiftTemplate\UpdateShiftTemplateHandler;
use App\Modules\Shift\Application\Commands\ShiftTemplate\ActivateShiftTemplateCommand;
use App\Modules\Shift\Application\Commands\ShiftTemplate\CreateShiftTemplateCommand;
use App\Modules\Shift\Application\Commands\ShiftTemplate\DeactivateShiftTemplateCommand;
use App\Modules\Shift\Application\Commands\ShiftTemplate\UpdateShiftTemplateCommand;
use App\Modules\Shift\Infrastructure\Http\Resources\ShiftTemplateResource;
use App\Modules\Shift\Infrastructure\Persistence\Eloquent\ShiftTemplateModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftTemplateController
{
    public function __construct(
        private CreateShiftTemplateHandler $createHandler,
        private UpdateShiftTemplateHandler $updateHandler,
        private ActivateShiftTemplateHandler $activateHandler,
        private DeactivateShiftTemplateHandler $deactivateHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = ShiftTemplateModel::query()->orderBy('name')
            ->paginate((int) $request->input('per_page', 20), ['*'], 'page', (int) $request->input('page', 1));
        return response()->json(new PaginatedCollection($paginator->through(fn ($m) => new ShiftTemplateResource($m))));
    }

    public function store(Request $request): JsonResponse
    {
        $template = $this->createHandler->handle(new CreateShiftTemplateCommand(
            $request->input('code'),
            $request->input('name'),
            $request->input('start_time'),
            $request->input('end_time'),
            (int) $request->input('break_minutes', 0),
            (int) $request->input('late_tolerance_minutes', 0),
            $request->input('overtime_rules'),
            $request->input('flexibility_rules'),
            $request->input('payroll_attribution_rule'),
        ), (string) $request->user()->id);

        return response()->json(['data' => new ShiftTemplateResource(ShiftTemplateModel::find($template->id()->value))], 201);
    }

    public function show(string $id): JsonResponse
    {
        $model = ShiftTemplateModel::findOrFail($id);
        return response()->json(['data' => new ShiftTemplateResource($model)]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->updateHandler->handle(new UpdateShiftTemplateCommand(
            $id,
            $request->input('name'),
            $request->input('start_time'),
            $request->input('end_time'),
            (int) $request->input('break_minutes', 0),
            (int) $request->input('late_tolerance_minutes', 0),
            $request->input('overtime_rules'),
            $request->input('flexibility_rules'),
            $request->input('payroll_attribution_rule'),
        ), (string) $request->user()->id);

        return response()->json(['data' => new ShiftTemplateResource(ShiftTemplateModel::find($id))]);
    }

    public function activate(Request $request, string $id): JsonResponse
    {
        $this->activateHandler->handle(new ActivateShiftTemplateCommand($id), (string) $request->user()->id);
        return response()->json(['data' => new ShiftTemplateResource(ShiftTemplateModel::find($id))]);
    }

    public function deactivate(Request $request, string $id): JsonResponse
    {
        $this->deactivateHandler->handle(new DeactivateShiftTemplateCommand($id), (string) $request->user()->id);
        return response()->json(['data' => new ShiftTemplateResource(ShiftTemplateModel::find($id))]);
    }
}
