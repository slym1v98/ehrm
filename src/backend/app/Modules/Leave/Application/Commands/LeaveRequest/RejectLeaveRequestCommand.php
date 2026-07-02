<?php
namespace App\Modules\Leave\Application\Commands\LeaveRequest;
class RejectLeaveRequestCommand { public function __construct(public string $id, public string $rejectedBy, public string $reason) {} }
