<?php

namespace App\Modules\Attendance\Application\CommandHandlers\AttendancePeriod;

use App\Modules\Attendance\Application\Commands\AttendancePeriod\CloseAttendancePeriodCommand;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;

class CloseAttendancePeriodHandler
{
    public function __construct(
        private AttendancePeriodRepositoryInterface $repository,
    ) {}

    public function handle(CloseAttendancePeriodCommand $command): void
    {
        $period = $this->repository->findById($command->periodId);
        $period->close();
        $this->repository->saveAndDispatch($period);
    }
}
