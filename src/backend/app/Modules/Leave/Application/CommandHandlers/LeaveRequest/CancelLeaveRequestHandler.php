<?php
namespace App\Modules\Leave\Application\CommandHandlers\LeaveRequest;
use App\Modules\Leave\Application\Commands\LeaveRequest\CancelLeaveRequestCommand;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequest;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequestId;
use App\Modules\Leave\Domain\Exceptions\LeaveRequestNotFoundException;
use App\Modules\Leave\Domain\Repositories\LeaveBalanceRepositoryInterface;
use App\Modules\Leave\Domain\Repositories\LeaveRequestRepositoryInterface;
use App\Modules\Leave\Domain\Repositories\LeaveTypeRepositoryInterface;
use App\Modules\Leave\Domain\ValueObjects\LeaveStatus;
use Illuminate\Support\Facades\Event;
class CancelLeaveRequestHandler { public function __construct(private LeaveRequestRepositoryInterface $requests, private LeaveTypeRepositoryInterface $types, private LeaveBalanceRepositoryInterface $balances) {} public function handle(CancelLeaveRequestCommand $command): LeaveRequest { $request=$this->requests->findById(new LeaveRequestId($command->id)); if(!$request) throw new LeaveRequestNotFoundException('Leave request not found'); $wasApproved=$request->status()===LeaveStatus::APPROVED; Event::dispatch($request->cancel($command->cancelledBy)); if($wasApproved && $this->types->findById($request->leaveTypeId())?->isBalanceTracked()){ $balance=$this->balances->findByEmployeeTypeYear($request->employeeId(), $request->leaveTypeId(), (int)$request->period()->startAt()->format('Y')); if($balance){ Event::dispatch($balance->restore($request->period()->durationMinutes())); $this->balances->save($balance); } } $this->requests->save($request); return $request; } }
