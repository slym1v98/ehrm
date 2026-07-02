<?php

namespace App\Modules\Attendance\Infrastructure\Persistence\Repositories;

use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLog;
use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLogId;
use App\Modules\Attendance\Domain\Repositories\AttendanceRawLogRepositoryInterface;
use App\Modules\Attendance\Infrastructure\Persistence\Eloquent\AttendanceRawLogModel;

class EloquentAttendanceRawLogRepository implements AttendanceRawLogRepositoryInterface
{
    public function saveAndDispatch(AttendanceRawLog $rawLog): void
    {
        $model = new AttendanceRawLogModel();
        $model->id = $rawLog->id()->toString();
        $model->employee_id = $rawLog->employeeId();
        $model->source = $rawLog->source()->value;
        $model->event_type = $rawLog->eventType()->value;
        $model->event_time = $rawLog->eventTime();
        $model->geo_point = $rawLog->geoPoint()?->toArray();
        $model->payload = $rawLog->payload();
        $model->created_at = now();
        $model->save();

        foreach ($rawLog->releaseEvents() as $event) {
            event($event);
        }
    }

    public function findPaginated(int $perPage = 15, int $page = 1): array
    {
        return AttendanceRawLogModel::orderBy('event_time', 'desc')->paginate($perPage)->items();
    }

    public function findByEmployeeAndRange(string $employeeId, string $from, string $to): array
    {
        return AttendanceRawLogModel::where('employee_id', $employeeId)
            ->whereBetween('event_time', [$from, $to])
            ->orderBy('event_time')
            ->get()
            ->all();
    }
}
