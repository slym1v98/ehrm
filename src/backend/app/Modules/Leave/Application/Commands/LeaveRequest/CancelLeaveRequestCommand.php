<?php
namespace App\Modules\Leave\Application\Commands\LeaveRequest;
class CancelLeaveRequestCommand { public function __construct(public string $id, public string $cancelledBy) {} }
