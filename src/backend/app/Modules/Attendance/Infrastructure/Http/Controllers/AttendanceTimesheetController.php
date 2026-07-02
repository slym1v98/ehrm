<?php

namespace App\Modules\Attendance\Infrastructure\Http\Controllers;

use App\Modules\Attendance\Application\CommandHandlers\AttendanceTimesheet\CalculateAttendanceForPeriodHandler;
use App\Modules\Attendance\Application\Commands\AttendanceTimesheet\CalculateAttendanceForPeriodCommand;
use App\Modules\Attendance\Infrastructure\Http\Requests\CalculateAttendanceRequest;
use App\Modules\Attendance\Infrastructure\Http\Resources\AttendanceTimesheetResource;
use App\Modules\Attendance\Infrastructure\Persistence\Eloquent\AttendanceTimesheetModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceTimesheetController
{
    public function __construct(private CalculateAttendanceForPeriodHandler $calculateHandler) {}

    public function index(Request $request): JsonResponse
    {
        $query = AttendanceTimesheetModel::query()->orderByDesc('work_date');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('work_date', [$request->input('from'), $request->input('to')]);
        }

        $paginator = $query->paginate((int) $request->input('per_page', 20), ['*'], 'page', (int) $request->input('page', 1));

        return response()->json(new PaginatedCollection($paginator->through(fn ($m) => new AttendanceTimesheetResource($m))));
    }

    public function employeeAttendance(string $id, Request $request): JsonResponse
    {
        $rows = AttendanceTimesheetModel::query()
            ->where('employee_id', $id)
            ->when($request->filled('from') && $request->filled('to'), fn ($q) => $q->whereBetween('work_date', [$request->input('from'), $request->input('to')]))
            ->orderByDesc('work_date')
            ->get();

        return response()->json([
            'data' => AttendanceTimesheetResource::collection($rows),
        ]);
    }

    public function calculate(CalculateAttendanceRequest $request): JsonResponse
    {
        $this->calculateHandler->handle(new CalculateAttendanceForPeriodCommand(
            employeeId: $request->string('employee_id')->toString(),
            from: $request->string('from')->toString(),
            to: $request->string('to')->toString(),
        ));

        return response()->json(['message' => 'Calculation queued/completed'], 202);
    }
}
