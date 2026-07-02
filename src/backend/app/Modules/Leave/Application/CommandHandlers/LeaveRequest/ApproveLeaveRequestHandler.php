<?php
namespace App\Modules\Leave\Application\CommandHandlers\LeaveRequest;
use App\Modules\Leave\Application\Commands\LeaveRequest\ApproveLeaveRequestCommand;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequest;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequestId;
use App\Modules\Leave\Domain\Exceptions\LeaveBalanceNotFoundException;
use App\Modules\Leave\Domain\Exceptions\LeaveRequestNotFoundException;
use App\Modules\Leave\Domain\Repositories\LeaveBalanceRepositoryInterface;
use App\Modules\Leave\Domain\Repositories\LeaveRequestRepositoryInterface;
use App\Modules\Leave\Domain\Repositories\LeaveTypeRepositoryInterface;
use Illuminate\Support\Facades\Event;
class ApproveLeaveRequestHandler { public function __construct(private LeaveRequestRepositoryInterface $requests, private LeaveTypeRepositoryInterface $types, private LeaveBalanceRepositoryInterface $balances) {} public function handle(ApproveLeaveRequestCommand $command): LeaveRequest { $request=$this->requests->findById(new LeaveRequestId($command->id)); if(!$request) throw new LeaveRequestNotFoundException('Leave request not found'); $type=$this->types->findById($request->leaveTypeId()); $balanceBefore=null; if($type?->isBalanceTracked()){ $balance=$this->balances->findByEmployeeTypeYear($request->employeeId(), $request->leaveTypeId(), (int)$request->period()->startAt()->format('Y')); if(!$balance) throw new LeaveBalanceNotFoundException('Leave balance not found'); $balanceBefore=$balance->remaining(); Event::dispatch($balance->deduct($request->period()->durationMinutes())); $this->balances->save($balance); } Event::dispatch($request->approve($command->approvedBy, $balanceBefore)); $this->requests->save($request); return $request; } }
