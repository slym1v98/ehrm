<?php

namespace App\Modules\Leave\Infrastructure\Persistence\Repositories;

use App\Modules\Leave\Domain\Aggregates\LeaveBalance\LeaveBalance;
use App\Modules\Leave\Domain\Aggregates\LeaveBalance\LeaveBalanceId;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Repositories\LeaveBalanceRepositoryInterface;
use App\Modules\Leave\Infrastructure\Persistence\Eloquent\LeaveBalanceModel;

class EloquentLeaveBalanceRepository implements LeaveBalanceRepositoryInterface
{
    public function __construct(private LeaveBalanceModel $model) {}

    public function findByEmployeeTypeYear(string $employeeId, LeaveTypeId $typeId, int $year): ?LeaveBalance
    {
        $record = $this->model
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $typeId->value())
            ->where('year', $year)
            ->first();
        return $record ? self::toDomain($record) : null;
    }

    public function findByEmployee(string $employeeId, ?int $year = null): array
    {
        $query = $this->model->where('employee_id', $employeeId);
        if ($year) {
            $query->where('year', $year);
        }
        return $query->get()->map(fn($r) => self::toDomain($r))->all();
    }

    public function save(LeaveBalance $balance): void
    {
        $this->model->updateOrCreate(
            ['id' => $balance->id()->value()],
            [
                'employee_id' => $balance->employeeId(),
                'leave_type_id' => $balance->leaveTypeId()->value(),
                'year' => $balance->year(),
                'opening' => $balance->opening(),
                'accrued' => $balance->accrued(),
                'used' => $balance->used(),
                'carried_over' => $balance->carriedOver(),
                'expired' => $balance->expired(),
            ],
        );
    }

    public static function toDomain(LeaveBalanceModel $model): LeaveBalance
    {
        return new LeaveBalance(
            new LeaveBalanceId($model->id),
            $model->employee_id,
            new LeaveTypeId($model->leave_type_id),
            $model->year,
            $model->opening,
            $model->accrued,
            $model->used,
            $model->carried_over,
            $model->expired,
        );
    }
}
