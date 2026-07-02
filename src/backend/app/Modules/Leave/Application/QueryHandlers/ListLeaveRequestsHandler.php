<?php
namespace App\Modules\Leave\Application\QueryHandlers;
use App\Modules\Leave\Application\Queries\LeaveRequest\ListLeaveRequestsQuery;
use App\Modules\Leave\Domain\Repositories\LeaveRequestRepositoryInterface;
class ListLeaveRequestsHandler { public function __construct(private LeaveRequestRepositoryInterface $requests) {} public function handle(ListLeaveRequestsQuery $query): mixed { return $this->requests->findByEmployee($query->employeeId, $query->filters, $query->perPage); } }
