<?php

namespace App\Modules\Shift\Infrastructure\Http\Controllers;

use App\Modules\Shift\Application\CommandHandlers\ShiftAssignment\AssignShiftHandler;
use App\Modules\Shift\Application\CommandHandlers\ShiftAssignment\ChangeShiftAssignmentHandler;
use App\Modules\Shift\Application\CommandHandlers\ShiftAssignment\EndShiftAssignmentHandler;
use App\Modules\Shift\Application\Commands\ShiftAssignment\AssignShiftCommand;
use App\Modules\Shift\Application\Commands\ShiftAssignment\ChangeShiftAssignmentCommand;
use App\Modules\Shift\Application\Commands\ShiftAssignment\EndShiftAssignmentCommand;
use App\Modules\Shift\Infrastructure\Http\Resources\ShiftAssignmentResource;
use App\Modules\Shift\Infrastructure\Persistence\Eloquent\ShiftAssignmentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftAssignmentController
{
    public function __construct(
        private AssignShiftHandler $assignHandler,
        private ChangeShiftAssignmentHandler $changeHandler,
        private EndShiftAssignmentHandler $endHandler,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $assignment = $this->assignHandler->handle(new AssignShiftCommand(
            $request->input('shift_template_id'),
            $request->input('assignable_type'),
            $request->input('assignable_id'),
            $request->input('effective_from'),
            $request->input('effective_to'),
            $request->input('recurrence_rule'),
        ), (string) $request->user()->id);

        return response()->json(['data' => new ShiftAssignmentResource(ShiftAssignmentModel::find($assignment->id()->value))], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->changeHandler->handle(new ChangeShiftAssignmentCommand(
            $id,
            $request->input('new_template_id'),
            $request->input('effective_from'),
        ), (string) $request->user()->id);

        return response()->json(['data' => new ShiftAssignmentResource(ShiftAssignmentModel::find($id))]);
    }

    public function end(Request $request, string $id): JsonResponse
    {
        $this->endHandler->handle(new EndShiftAssignmentCommand($id, $request->input('effective_to')), (string) $request->user()->id);
        return response()->json(['data' => new ShiftAssignmentResource(ShiftAssignmentModel::find($id))]);
    }

    public function employeeShifts(string $id, Request $request): JsonResponse
    {
        $assignments = ShiftAssignmentModel::with('shiftTemplate')
            ->where('assignable_type', 'employee')
            ->where('assignable_id', $id)
            ->where('active', true)
            ->get();

        return response()->json(['data' => ShiftAssignmentResource::collection($assignments)]);
    }

    public function departmentShifts(string $id, Request $request): JsonResponse
    {
        $assignments = ShiftAssignmentModel::with('shiftTemplate')
            ->where('assignable_type', 'department')
            ->where('assignable_id', $id)
            ->where('active', true)
            ->get();

        return response()->json(['data' => ShiftAssignmentResource::collection($assignments)]);
    }
}
