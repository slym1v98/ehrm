<?php

namespace App\Modules\Attendance\Infrastructure\Persistence\Repositories;

use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriod;
use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriodId;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;
use App\Modules\Attendance\Domain\ValueObjects\PeriodStatus;
use App\Modules\Attendance\Infrastructure\Persistence\Eloquent\AttendancePeriodModel;
use Carbon\CarbonImmutable;

class EloquentAttendancePeriodRepository implements AttendancePeriodRepositoryInterface
{
    public function findById(string $id): ?AttendancePeriod
    {
        $model = AttendancePeriodModel::find($id);
        return $model ? $this->toAggregate($model) : null;
    }

    public function findByCode(string $code): ?AttendancePeriod
    {
        $model = AttendancePeriodModel::where('period_code', $code)->first();
        return $model ? $this->toAggregate($model) : null;
    }

    public function saveAndDispatch(AttendancePeriod $period): void
    {
        AttendancePeriodModel::updateOrCreate(
            ['id' => $period->id()->toString()],
            [
                'period_code' => $period->periodCode(),
                'start_date' => $period->startDate()->format('Y-m-d'),
                'end_date' => $period->endDate()->format('Y-m-d'),
                'status' => $period->status()->value,
            ]
        );

        foreach ($period->releaseEvents() as $event) {
            event($event);
        }
    }

    public function findClosedByDate(string $date): ?AttendancePeriod
    {
        $model = AttendancePeriodModel::where("status", "closed")
            ->where("start_date", "<=", $date)
            ->where("end_date", ">=", $date)
            ->first();
        return $model ? $this->toAggregate($model) : null;
    }

        public function findPaginated(int $perPage = 15, int $page = 1): array
    {
        return AttendancePeriodModel::paginate($perPage)->items();
    }

    private function toAggregate(AttendancePeriodModel $model): AttendancePeriod
    {
        return AttendancePeriod::reconstitute(
            id: AttendancePeriodId::fromString($model->id),
            periodCode: $model->period_code,
            startDate: CarbonImmutable::instance($model->start_date),
            endDate: CarbonImmutable::instance($model->end_date),
            status: PeriodStatus::from($model->status),
        );
    }
}
