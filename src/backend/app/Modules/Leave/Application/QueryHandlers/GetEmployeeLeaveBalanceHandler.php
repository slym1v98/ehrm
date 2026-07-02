<?php
namespace App\Modules\Leave\Application\QueryHandlers;
use App\Modules\Leave\Application\Queries\LeaveRequest\GetEmployeeLeaveBalanceQuery;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Repositories\LeaveBalanceRepositoryInterface;
class GetEmployeeLeaveBalanceHandler { public function __construct(private LeaveBalanceRepositoryInterface $balances) {} public function handle(GetEmployeeLeaveBalanceQuery $query): mixed { return $query->leaveTypeId ? $this->balances->findByEmployeeTypeYear($query->employeeId, new LeaveTypeId($query->leaveTypeId), $query->year ?? (int)date('Y')) : $this->balances->findByEmployee($query->employeeId, $query->year); } }
