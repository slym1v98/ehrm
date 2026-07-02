<?php
namespace App\Modules\Leave\Infrastructure\Http\Controllers;
use App\Modules\Leave\Application\CommandHandlers\LeaveRequest\ApproveLeaveRequestHandler;
use App\Modules\Leave\Application\CommandHandlers\LeaveRequest\CancelLeaveRequestHandler;
use App\Modules\Leave\Application\CommandHandlers\LeaveRequest\RejectLeaveRequestHandler;
use App\Modules\Leave\Application\CommandHandlers\LeaveRequest\SubmitLeaveRequestHandler;
use App\Modules\Leave\Application\Commands\LeaveRequest\ApproveLeaveRequestCommand;
use App\Modules\Leave\Application\Commands\LeaveRequest\CancelLeaveRequestCommand;
use App\Modules\Leave\Application\Commands\LeaveRequest\RejectLeaveRequestCommand;
use App\Modules\Leave\Application\Commands\LeaveRequest\SubmitLeaveRequestCommand;
use App\Modules\Leave\Application\Queries\LeaveRequest\GetLeaveRequestQuery;
use App\Modules\Leave\Application\Queries\LeaveRequest\ListLeaveRequestsQuery;
use App\Modules\Leave\Application\QueryHandlers\GetLeaveRequestHandler;
use App\Modules\Leave\Application\QueryHandlers\ListLeaveRequestsHandler;
use App\Modules\Leave\Infrastructure\Http\Requests\ApproveLeaveRequest;
use App\Modules\Leave\Infrastructure\Http\Requests\CancelLeaveRequest;
use App\Modules\Leave\Infrastructure\Http\Requests\RejectLeaveRequest;
use App\Modules\Leave\Infrastructure\Http\Requests\SubmitLeaveRequest;
use App\Modules\Leave\Infrastructure\Http\Resources\LeaveRequestResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LeaveRequestController extends Controller
{
    public function store(SubmitLeaveRequest $req, SubmitLeaveRequestHandler $handler) {
        $uid = $req->user()->employee_id ?? $req->user()->id;
        $cmd = new SubmitLeaveRequestCommand($uid, $req->leave_type_id, $req->start_at, $req->end_at, $req->duration_unit, $req->reason);
        return new LeaveRequestResource($handler->handle($cmd));
    }

    public function index(Request $req, ListLeaveRequestsHandler $handler) {
        $uid = $req->user()->employee_id ?? $req->user()->id;
        $filters = $req->only(['status', 'leave_type_id', 'from', 'to']);
        $result = $handler->handle(new ListLeaveRequestsQuery($uid, $filters));
        return LeaveRequestResource::collection($result);
    }

    public function show(string $id, GetLeaveRequestHandler $handler) {
        return new LeaveRequestResource($handler->handle(new GetLeaveRequestQuery($id)));
    }

    public function approve(string $id, ApproveLeaveRequest $req, ApproveLeaveRequestHandler $handler) {
        return new LeaveRequestResource($handler->handle(new ApproveLeaveRequestCommand($id, $req->user()->id)));
    }

    public function reject(string $id, RejectLeaveRequest $req, RejectLeaveRequestHandler $handler) {
        return new LeaveRequestResource($handler->handle(new RejectLeaveRequestCommand($id, $req->user()->id, $req->reason)));
    }

    public function cancel(string $id, CancelLeaveRequest $req, CancelLeaveRequestHandler $handler) {
        return new LeaveRequestResource($handler->handle(new CancelLeaveRequestCommand($id, $req->user()->id)));
    }
}
