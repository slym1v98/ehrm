<?php

namespace App\Modules\Attendance\Application\CommandHandlers\AttendancePeriod;

use App\Modules\Attendance\Application\Commands\AttendancePeriod\ReopenAttendancePeriodCommand;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;

class ReopenAttendancePeriodHandler
{
    public function __construct(
        private AttendancePeriodRepositoryInterface $repository,
    ) {}

    public function handle(ReopenAttendancePeriodCommand $command): void
    {
        $period = $this->repository->findById($command->periodId);
        $period->reopen($command->reason);
        $this->repository->saveAndDispatch($period);
    }
}
