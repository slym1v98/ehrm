<?php

namespace App\Modules\Attendance\Infrastructure\Http\Controllers;

use App\Modules\Attendance\Application\CommandHandlers\AttendanceAdjustment\ApproveAttendanceAdjustmentHandler;
use App\Modules\Attendance\Application\CommandHandlers\AttendanceAdjustment\RejectAttendanceAdjustmentHandler;
use App\Modules\Attendance\Application\CommandHandlers\AttendanceAdjustment\SubmitAttendanceAdjustmentHandler;
use App\Modules\Attendance\Application\Commands\AttendanceAdjustment\ApproveAttendanceAdjustmentCommand;
use App\Modules\Attendance\Application\Commands\AttendanceAdjustment\RejectAttendanceAdjustmentCommand;
use App\Modules\Attendance\Application\Commands\AttendanceAdjustment\SubmitAttendanceAdjustmentCommand;
use App\Modules\Attendance\Infrastructure\Http\Requests\SubmitAttendanceAdjustmentRequest;
use App\Modules\Attendance\Infrastructure\Http\Resources\AttendanceAdjustmentRequestResource;
use App\Modules\Attendance\Infrastructure\Persistence\Eloquent\AttendanceAdjustmentRequestModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceAdjustmentController
{
    public function __construct(
        private SubmitAttendanceAdjustmentHandler $submitHandler,
        private ApproveAttendanceAdjustmentHandler $approveHandler,
        private RejectAttendanceAdjustmentHandler $rejectHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = AttendanceAdjustmentRequestModel::query()
            ->when($request->boolean('pending_only', true), fn ($q) => $q->where('status', 'pending'))
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20), ['*'], 'page', (int) $request->input('page', 1));

        return response()->json(new PaginatedCollection($paginator->through(fn ($m) => new AttendanceAdjustmentRequestResource($m))));
    }

    public function store(SubmitAttendanceAdjustmentRequest $request): JsonResponse
    {
        $adjustment = $this->submitHandler->handle(new SubmitAttendanceAdjustmentCommand(
            attendanceTimesheetId: $request->string('attendance_timesheet_id')->toString(),
            employeeId: $request->string('employee_id')->toString(),
            requestedBy: $request->user()?->id ?? $request->string('requested_by')->toString(),
            corrections: $request->input('corrections', []),
            reason: $request->string('reason')->toString(),
            evidenceFile: $request->input('evidence_file'),
        ));

        return response()->json([
            'data' => new AttendanceAdjustmentRequestResource(AttendanceAdjustmentRequestModel::findOrFail($adjustment->id()->toString())),
        ], 201);
    }

    public function approve(string $id, Request $request): JsonResponse
    {
        $this->approveHandler->handle(new ApproveAttendanceAdjustmentCommand($id, (string) $request->user()->id));
        return response()->json(['message' => 'Approved']);
    }

    public function reject(string $id, Request $request): JsonResponse
    {
        $this->rejectHandler->handle(new RejectAttendanceAdjustmentCommand($id, (string) $request->user()->id));
        return response()->json(['message' => 'Rejected']);
    }
}
