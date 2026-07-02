<?php

namespace App\Modules\Attendance\Infrastructure\Http\Controllers;

use App\Modules\Attendance\Application\CommandHandlers\AttendanceRawLog\RecordAttendanceRawLogHandler;
use App\Modules\Attendance\Application\Commands\AttendanceRawLog\RecordAttendanceRawLogCommand;
use App\Modules\Attendance\Infrastructure\Http\Requests\RecordAttendanceRawLogRequest;
use App\Modules\Attendance\Infrastructure\Http\Resources\AttendanceRawLogResource;
use App\Modules\Attendance\Infrastructure\Persistence\Eloquent\AttendanceRawLogModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Attendance\Domain\ValueObjects\Source;
use App\Modules\Attendance\Domain\ValueObjects\EventType;

class AttendanceRawLogController
{
    public function __construct(private RecordAttendanceRawLogHandler $handler) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = AttendanceRawLogModel::query()->orderByDesc('event_time')
            ->paginate((int) $request->input('per_page', 20), ['*'], 'page', (int) $request->input('page', 1));

        return response()->json(new PaginatedCollection($paginator->through(fn ($m) => new AttendanceRawLogResource($m))));
    }

    public function store(RecordAttendanceRawLogRequest $request): JsonResponse
    {
        $rawLog = $this->handler->handle(new RecordAttendanceRawLogCommand(
            employeeId: $request->string('employee_id')->toString(),
            source: Source::from($request->string('source')->toString()),
            eventType: EventType::from($request->string('event_type')->toString()),
            eventTime: $request->string('event_time')->toString(),
            geoPoint: $request->input('geo_point'),
            payload: $request->input('payload', []),
        ));

        return response()->json([
            'data' => new AttendanceRawLogResource(AttendanceRawLogModel::findOrFail($rawLog->id()->toString())),
        ], 201);
    }
}
