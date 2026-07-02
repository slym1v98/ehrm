<?php
namespace App\Modules\Leave\Application\Queries\LeaveRequest;
class GetEmployeeLeaveBalanceQuery { public function __construct(public string $employeeId, public ?string $leaveTypeId = null, public ?int $year = null) {} }
