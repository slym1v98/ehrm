<?php

namespace App\Modules\Attendance\Application\CommandHandlers\AttendancePeriod;

use App\Modules\Attendance\Application\Commands\AttendancePeriod\OpenAttendancePeriodCommand;
use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriod;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;
use Carbon\CarbonImmutable;

class OpenAttendancePeriodHandler
{
    public function __construct(
        private AttendancePeriodRepositoryInterface $repository,
    ) {}

    public function handle(OpenAttendancePeriodCommand $command): AttendancePeriod
    {
        $period = AttendancePeriod::open(
            periodCode: $command->periodCode,
            startDate: CarbonImmutable::parse($command->startDate),
            endDate: CarbonImmutable::parse($command->endDate),
        );

        $this->repository->saveAndDispatch($period);

        return $period;
    }
}
