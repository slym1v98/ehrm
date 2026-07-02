<?php
namespace App\Modules\Leave\Application\Commands\LeaveRequest;
class ApproveLeaveRequestCommand { public function __construct(public string $id, public string $approvedBy) {} }
