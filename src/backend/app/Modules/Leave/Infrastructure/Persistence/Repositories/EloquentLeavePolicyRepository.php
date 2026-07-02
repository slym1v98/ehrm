<?php

namespace App\Modules\Leave\Infrastructure\Persistence\Repositories;

use App\Modules\Leave\Domain\Aggregates\LeavePolicy\LeavePolicy;
use App\Modules\Leave\Domain\Aggregates\LeavePolicy\LeavePolicyId;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Repositories\LeavePolicyRepositoryInterface;
use App\Modules\Leave\Infrastructure\Persistence\Eloquent\LeavePolicyModel;
use Carbon\CarbonImmutable;

class EloquentLeavePolicyRepository implements LeavePolicyRepositoryInterface
{
    public function __construct(private LeavePolicyModel $model) {}

    public function findById(LeavePolicyId $id): ?LeavePolicy
    {
        $record = $this->model->find($id->value());
        return $record ? self::toDomain($record) : null;
    }

    public function findByType(LeaveTypeId $typeId, CarbonImmutable $date): ?LeavePolicy
    {
        $record = $this->model
            ->where('leave_type_id', $typeId->value())
            ->where('valid_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $date->toDateString());
            })
            ->orderByDesc('valid_from')
            ->first();
        return $record ? self::toDomain($record) : null;
    }

    public function all(): array
    {
        return $this->model->orderBy('valid_from')->get()->map(fn($r) => self::toDomain($r))->all();
    }

    public function save(LeavePolicy $policy): void
    {
        $this->model->updateOrCreate(
            ['id' => $policy->id()->value()],
            [
                'leave_type_id' => $policy->leaveTypeId()->value(),
                'valid_from' => $policy->validFrom()->toDateString(),
                'valid_until' => $policy->validUntil()?->toDateString(),
                'max_consecutive_days' => $policy->maxConsecutiveDays(),
                'requires_attachment' => $policy->requiresAttachment(),
                'carry_over_limit' => $policy->carryOverLimit(),
                'carry_over_expiry_months' => $policy->carryOverExpiryMonths(),
                'half_day_allowed' => $policy->halfDayAllowed(),
                'hourly_allowed' => $policy->hourlyAllowed(),
            ],
        );
    }

    public static function toDomain(LeavePolicyModel $model): LeavePolicy
    {
        return new LeavePolicy(
            new LeavePolicyId($model->id),
            new LeaveTypeId($model->leave_type_id),
            CarbonImmutable::parse($model->valid_from),
            $model->valid_until ? CarbonImmutable::parse($model->valid_until) : null,
            $model->max_consecutive_days,
            $model->requires_attachment,
            $model->carry_over_limit,
            $model->carry_over_expiry_months,
            $model->half_day_allowed,
            $model->hourly_allowed,
        );
    }
}
