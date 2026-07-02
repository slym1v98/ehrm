<?php

namespace App\Modules\Attendance\Infrastructure\Http\Controllers;

use App\Modules\Attendance\Application\CommandHandlers\AttendancePeriod\CloseAttendancePeriodHandler;
use App\Modules\Attendance\Application\CommandHandlers\AttendancePeriod\OpenAttendancePeriodHandler;
use App\Modules\Attendance\Application\CommandHandlers\AttendancePeriod\ReopenAttendancePeriodHandler;
use App\Modules\Attendance\Application\Commands\AttendancePeriod\CloseAttendancePeriodCommand;
use App\Modules\Attendance\Application\Commands\AttendancePeriod\OpenAttendancePeriodCommand;
use App\Modules\Attendance\Application\Commands\AttendancePeriod\ReopenAttendancePeriodCommand;
use App\Modules\Attendance\Infrastructure\Http\Requests\OpenAttendancePeriodRequest;
use App\Modules\Attendance\Infrastructure\Http\Requests\ReopenAttendancePeriodRequest;
use App\Modules\Attendance\Infrastructure\Http\Resources\AttendancePeriodResource;
use App\Modules\Attendance\Infrastructure\Persistence\Eloquent\AttendancePeriodModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendancePeriodController
{
    public function __construct(
        private OpenAttendancePeriodHandler $openHandler,
        private CloseAttendancePeriodHandler $closeHandler,
        private ReopenAttendancePeriodHandler $reopenHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = AttendancePeriodModel::query()->orderByDesc('start_date')
            ->paginate((int) $request->input('per_page', 20), ['*'], 'page', (int) $request->input('page', 1));

        return response()->json(new PaginatedCollection($paginator->through(fn ($m) => new AttendancePeriodResource($m))));
    }

    public function store(OpenAttendancePeriodRequest $request): JsonResponse
    {
        $period = $this->openHandler->handle(new OpenAttendancePeriodCommand(
            periodCode: $request->string('period_code')->toString(),
            startDate: $request->string('start_date')->toString(),
            endDate: $request->string('end_date')->toString(),
        ));

        return response()->json([
            'data' => new AttendancePeriodResource(AttendancePeriodModel::findOrFail($period->id()->toString())),
        ], 201);
    }

    public function close(string $id): JsonResponse
    {
        $this->closeHandler->handle(new CloseAttendancePeriodCommand($id));
        return response()->json(['message' => 'Closed']);
    }

    public function reopen(string $id, ReopenAttendancePeriodRequest $request): JsonResponse
    {
        $this->reopenHandler->handle(new ReopenAttendancePeriodCommand($id, $request->string('reason')->toString()));
        return response()->json(['message' => 'Reopened']);
    }
}
