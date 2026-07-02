<?php
namespace App\Modules\Leave\Application\QueryHandlers;
use App\Modules\Leave\Application\Queries\LeaveRequest\GetLeaveRequestQuery;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequestId;
use App\Modules\Leave\Domain\Exceptions\LeaveRequestNotFoundException;
use App\Modules\Leave\Domain\Repositories\LeaveRequestRepositoryInterface;
class GetLeaveRequestHandler { public function __construct(private LeaveRequestRepositoryInterface $requests) {} public function handle(GetLeaveRequestQuery $query): mixed { return $this->requests->findById(new LeaveRequestId($query->id)) ?? throw new LeaveRequestNotFoundException('Leave request not found'); } }
