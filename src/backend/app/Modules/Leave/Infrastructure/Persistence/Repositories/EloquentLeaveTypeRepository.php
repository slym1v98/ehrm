<?php

namespace App\Modules\Leave\Infrastructure\Persistence\Repositories;

use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveType;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Repositories\LeaveTypeRepositoryInterface;
use App\Modules\Leave\Infrastructure\Persistence\Eloquent\LeaveTypeModel;

class EloquentLeaveTypeRepository implements LeaveTypeRepositoryInterface
{
    public function __construct(private LeaveTypeModel $model) {}

    public function findById(LeaveTypeId $id): ?LeaveType
    {
        $record = $this->model->find($id->value());
        return $record ? self::toDomain($record) : null;
    }

    public function findByCode(string $code): ?LeaveType
    {
        $record = $this->model->where('code', $code)->first();
        return $record ? self::toDomain($record) : null;
    }

    public function all(): array
    {
        return $this->model->orderBy('sort_order')->get()->map(fn($r) => self::toDomain($r))->all();
    }

    public function save(LeaveType $type): void
    {
        $this->model->updateOrCreate(
            ['id' => $type->id()->value()],
            [
                'code' => $type->code(),
                'name' => $type->name(),
                'is_balance_tracked' => $type->isBalanceTracked(),
                'is_active' => $type->isActive(),
                'sort_order' => $type->sortOrder(),
            ],
        );
    }

    public static function toDomain(LeaveTypeModel $model): LeaveType
    {
        return new LeaveType(
            new LeaveTypeId($model->id),
            $model->code,
            $model->name,
            $model->is_balance_tracked,
            $model->is_active,
            $model->sort_order,
        );
    }
}
