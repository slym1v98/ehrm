<?php
namespace App\Modules\Leave\Application\CommandHandlers\LeaveRequest;
use App\Modules\Leave\Application\Commands\LeaveRequest\RejectLeaveRequestCommand;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequest;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequestId;
use App\Modules\Leave\Domain\Exceptions\LeaveRequestNotFoundException;
use App\Modules\Leave\Domain\Repositories\LeaveRequestRepositoryInterface;
use Illuminate\Support\Facades\Event;
class RejectLeaveRequestHandler { public function __construct(private LeaveRequestRepositoryInterface $requests) {} public function handle(RejectLeaveRequestCommand $command): LeaveRequest { $request=$this->requests->findById(new LeaveRequestId($command->id)); if(!$request) throw new LeaveRequestNotFoundException('Leave request not found'); Event::dispatch($request->reject($command->rejectedBy, $command->reason)); $this->requests->save($request); return $request; } }
