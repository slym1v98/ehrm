<?php
namespace App\Modules\Leave\Infrastructure\Http\Controllers;
use App\Modules\Leave\Application\Queries\LeaveRequest\GetEmployeeLeaveBalanceQuery;
use App\Modules\Leave\Application\QueryHandlers\GetEmployeeLeaveBalanceHandler;
use App\Modules\Leave\Infrastructure\Http\Resources\LeaveBalanceResource;
use App\Modules\Leave\Domain\Repositories\LeaveBalanceRepositoryInterface;
use App\Modules\Leave\Domain\Repositories\LeaveTypeRepositoryInterface;
use Illuminate\Routing\Controller;
class LeaveBalanceController extends Controller {
    public function __construct(private LeaveBalanceRepositoryInterface $balances, private LeaveTypeRepositoryInterface $types) {}
    public function index(GetEmployeeLeaveBalanceHandler $handler) {
        $userId = request()->user()->employee_id ?? request()->user()->id;
        $result = $handler->handle(new GetEmployeeLeaveBalanceQuery($userId, request('leave_type_id'), request('year')));
        return is_array($result) ? LeaveBalanceResource::collection($result) : new LeaveBalanceResource($result);
    }
    public function summary() {
        $userId = request()->user()->employee_id ?? request()->user()->id;
        $balances=$this->balances->findByEmployee($userId, (int)date('Y'));
        $types=$this->types->all();
        $result=[];
        foreach($types as $type){
            $bal=collect($balances)->first(fn($b)=>$b->leaveTypeId()->value()===$type->id()->value());
            $result[]=['leave_type_id'=>$type->id()->value(),'name'=>$type->name(),'code'=>$type->code(),'opening'=>$bal?->opening()??0,'accrued'=>$bal?->accrued()??0,'used'=>$bal?->used()??0,'carried_over'=>$bal?->carriedOver()??0,'expired'=>$bal?->expired()??0,'remaining'=>$bal?->remaining()??0];
        }
        return response()->json(['data'=>$result]);
    }
}
