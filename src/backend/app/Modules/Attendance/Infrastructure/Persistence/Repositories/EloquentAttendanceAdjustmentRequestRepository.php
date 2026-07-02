<?php

namespace App\Modules\Attendance\Infrastructure\Persistence\Repositories;

use App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest\AttendanceAdjustmentRequest;
use App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest\AttendanceAdjustmentRequestId;
use App\Modules\Attendance\Domain\Exceptions\DuplicatePendingAdjustmentException;
use App\Modules\Attendance\Domain\Repositories\AttendanceAdjustmentRequestRepositoryInterface;
use App\Modules\Attendance\Domain\ValueObjects\AdjustmentStatus;
use App\Modules\Attendance\Infrastructure\Persistence\Eloquent\AttendanceAdjustmentRequestModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

class EloquentAttendanceAdjustmentRequestRepository implements AttendanceAdjustmentRequestRepositoryInterface
{
    public function findById(string $id): ?AttendanceAdjustmentRequest
    {
        $model = AttendanceAdjustmentRequestModel::find($id);
        return $model ? $this->toAggregate($model) : null;
    }

    public function hasPendingForTimesheet(string $timesheetId): bool
    {
        return AttendanceAdjustmentRequestModel::where('attendance_timesheet_id', $timesheetId)
            ->where('status', 'pending')
            ->exists();
    }

    public function saveAndDispatch(AttendanceAdjustmentRequest $request): void
    {
        try {
            AttendanceAdjustmentRequestModel::updateOrCreate(
                ['id' => $request->id()->toString()],
                [
                    'attendance_timesheet_id' => $request->attendanceTimesheetId(),
                    'employee_id' => $request->employeeId(),
                    'requested_by' => $request->requestedBy(),
                    'reason' => $request->reason(),
                    'evidence_file' => $request->evidenceFile(),
                    'corrections' => $request->corrections(),
                    'status' => $request->status()->value,
                    'approved_by' => $request->approvedBy(),
                    'approved_at' => $request->approvedAt(),
                ]
            );
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'uniq_adj_req_pending')) {
                throw new DuplicatePendingAdjustmentException($request->attendanceTimesheetId());
            }
            throw $e;
        }

        foreach ($request->releaseEvents() as $event) {
            event($event);
        }
    }

    public function findPendingPaginated(int $perPage = 15, int $page = 1): array
    {
        return AttendanceAdjustmentRequestModel::where('status', 'pending')->paginate($perPage)->items();
    }

    private function toAggregate(AttendanceAdjustmentRequestModel $model): AttendanceAdjustmentRequest
    {
        return AttendanceAdjustmentRequest::reconstitute(
            id: AttendanceAdjustmentRequestId::fromString($model->id),
            attendanceTimesheetId: $model->attendance_timesheet_id,
            employeeId: $model->employee_id,
            requestedBy: $model->requested_by,
            reason: $model->reason,
            evidenceFile: $model->evidence_file,
            corrections: $model->corrections ?? [],
            status: AdjustmentStatus::from($model->status),
            approvedBy: $model->approved_by,
            approvedAt: $model->approved_at ? CarbonImmutable::instance($model->approved_at) : null,
        );
    }
}
