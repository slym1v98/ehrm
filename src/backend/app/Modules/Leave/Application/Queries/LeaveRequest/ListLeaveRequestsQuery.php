<?php
namespace App\Modules\Leave\Application\Queries\LeaveRequest;
class ListLeaveRequestsQuery { public function __construct(public ?string $employeeId, public array $filters = [], public int $perPage = 15) {} }
