<?php
namespace App\Modules\Leave\Application\Commands\LeaveRequest;
class SubmitLeaveRequestCommand { public function __construct(public string $employeeId, public string $leaveTypeId, public string $startAt, public string $endAt, public string $durationUnit, public ?string $reason) {} }
