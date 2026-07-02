<?php
namespace App\Modules\Leave\Infrastructure\Persistence\Repositories;
use App\Modules\Leave\Domain\Services\LeaveWindowInterface;
use App\Modules\Leave\Domain\ValueObjects\DurationUnit;
use App\Modules\Leave\Domain\ValueObjects\LeavePeriod;
use App\Modules\Leave\Domain\ValueObjects\LeaveStatus;
use App\Modules\Leave\Infrastructure\Persistence\Eloquent\LeaveRequestModel;
use Carbon\CarbonImmutable;
class EloquentLeaveWindowRepository implements LeaveWindowInterface { public function __construct(private LeaveRequestModel $model) {} public function getLeaveWindows(string $employeeId, CarbonImmutable $start, CarbonImmutable $end): array { return $this->model->where('employee_id',$employeeId)->where('status',LeaveStatus::APPROVED->value)->where('start_at','<=',$end->toDateString())->where('end_at','>=',$start->toDateString())->get()->map(fn($r)=>new LeavePeriod(CarbonImmutable::parse($r->start_at), CarbonImmutable::parse($r->end_at), DurationUnit::from($r->duration_unit), $r->duration_minutes))->all(); } }
