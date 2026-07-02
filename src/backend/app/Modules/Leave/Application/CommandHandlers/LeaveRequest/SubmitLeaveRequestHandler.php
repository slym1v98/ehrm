<?php

namespace App\Modules\Leave\Application\CommandHandlers\LeaveRequest;

use App\Modules\Leave\Application\Commands\LeaveRequest\SubmitLeaveRequestCommand;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequest;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequestId;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Exceptions\InsufficientBalanceException;
use App\Modules\Leave\Domain\Exceptions\LeavePolicyNotFoundException;
use App\Modules\Leave\Domain\Exceptions\LeaveTypeNotFoundException;
use App\Modules\Leave\Domain\Exceptions\OverlappingLeaveException;
use App\Modules\Leave\Domain\Repositories\LeaveBalanceRepositoryInterface;
use App\Modules\Leave\Domain\Repositories\LeavePolicyRepositoryInterface;
use App\Modules\Leave\Domain\Repositories\LeaveRequestRepositoryInterface;
use App\Modules\Leave\Domain\Repositories\LeaveTypeRepositoryInterface;
use App\Modules\Leave\Domain\ValueObjects\DurationUnit;
use App\Modules\Leave\Domain\ValueObjects\LeavePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

class SubmitLeaveRequestHandler
{
    public function __construct(private LeaveTypeRepositoryInterface $types, private LeavePolicyRepositoryInterface $policies, private LeaveRequestRepositoryInterface $requests, private LeaveBalanceRepositoryInterface $balances) {}

    public function handle(SubmitLeaveRequestCommand $command): LeaveRequest
    {
        $typeId = new LeaveTypeId($command->leaveTypeId);
        $type = $this->types->findById($typeId);
        if (!$type || !$type->isActive()) throw new LeaveTypeNotFoundException('Active leave type not found');
        $start = CarbonImmutable::parse($command->startAt);
        $end = CarbonImmutable::parse($command->endAt);
        $unit = DurationUnit::from($command->durationUnit);
        $minutes = $unit->defaultMinutes() * ($unit === DurationUnit::DAY ? $start->diffInDays($end) + 1 : 1);
        $period = new LeavePeriod($start, $end, $unit, $minutes);
        $policy = $this->policies->findByType($typeId, $start);
        if (!$policy || !$policy->allowsDuration($unit)) throw new LeavePolicyNotFoundException('Applicable leave policy not found');
        if ($policy->maxConsecutiveDays() && ($start->diffInDays($end) + 1) > $policy->maxConsecutiveDays()) throw new InsufficientBalanceException('Leave exceeds policy max consecutive days');
        if ($this->requests->findOverlapping($command->employeeId, $start, $end)) throw new OverlappingLeaveException('Overlapping leave request');
        if ($type->isBalanceTracked()) {
            $balance = $this->balances->findByEmployeeTypeYear($command->employeeId, $typeId, (int) $start->format('Y'));
            if (!$balance || $balance->remaining() < $minutes) throw new InsufficientBalanceException('Insufficient leave balance');
        }
        $request = new LeaveRequest(LeaveRequestId::new(), $command->employeeId, $typeId, $period, $unit, $command->reason);
        $this->requests->save($request);
        Event::dispatch($request->submittedEvent());
        return $request;
    }
}
