<?php

namespace App\Modules\Leave\Infrastructure\Persistence\Repositories;

use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequest;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequestId;
use App\Modules\Leave\Domain\Repositories\LeaveRequestRepositoryInterface;
use App\Modules\Leave\Domain\ValueObjects\DurationUnit;
use App\Modules\Leave\Domain\ValueObjects\LeavePeriod;
use App\Modules\Leave\Domain\ValueObjects\LeaveStatus;
use App\Modules\Leave\Infrastructure\Persistence\Eloquent\LeaveRequestModel;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentLeaveRequestRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(private LeaveRequestModel $model) {}

    public function findById(LeaveRequestId $id): ?LeaveRequest
    {
        $record = $this->model->find($id->value());
        return $record ? self::toDomain($record) : null;
    }

    public function findOverlapping(string $employeeId, CarbonImmutable $start, CarbonImmutable $end, ?LeaveRequestId $excludeId = null): array
    {
        $query = $this->model
            ->where('employee_id', $employeeId)
            ->where('status', LeaveStatus::PENDING->value)
            ->where('start_at', '<=', $end->toDateString())
            ->where('end_at', '>=', $start->toDateString());
        if ($excludeId) {
            $query->where('id', '!=', $excludeId->value());
        }
        return $query->get()->map(fn($r) => self::toDomain($r))->all();
    }

    public function findByEmployee(?string $employeeId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }
        if (!empty($filters['from'])) {
            $query->where('start_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('end_at', '<=', $filters['to']);
        }
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function save(LeaveRequest $request): void
    {
        $this->model->updateOrCreate(
            ['id' => $request->id()->value()],
            [
                'employee_id' => $request->employeeId(),
                'leave_type_id' => $request->leaveTypeId()->value(),
                'start_at' => $request->period()->startAt()->toDateString(),
                'end_at' => $request->period()->endAt()->toDateString(),
                'duration_unit' => $request->durationUnit()->value,
                'duration_minutes' => $request->period()->durationMinutes(),
                'reason' => $request->reason(),
                'status' => $request->status()->value,
                'approved_by' => $request->approvedBy(),
                'approved_at' => $request->approvedAt(),
                'rejected_reason' => $request->rejectedReason(),
                'balance_before' => $request->balanceBefore(),
            ],
        );
    }

    public static function toDomain(LeaveRequestModel $model): LeaveRequest
    {
        $status = LeaveStatus::from($model->status);
        return new LeaveRequest(
            new LeaveRequestId($model->id),
            $model->employee_id,
            new LeaveTypeId($model->leave_type_id),
            new LeavePeriod(
                CarbonImmutable::parse($model->start_at),
                CarbonImmutable::parse($model->end_at),
                DurationUnit::from($model->duration_unit),
                $model->duration_minutes,
            ),
            DurationUnit::from($model->duration_unit),
            $model->reason,
            $status,
            $model->approved_by,
            $model->approved_at ? CarbonImmutable::parse($model->approved_at) : null,
            $model->rejected_reason,
            $model->balance_before,
        );
    }
}
