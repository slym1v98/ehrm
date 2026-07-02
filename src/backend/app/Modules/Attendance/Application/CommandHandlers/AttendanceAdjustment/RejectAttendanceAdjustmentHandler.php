<?php

namespace App\Modules\Attendance\Application\CommandHandlers\AttendanceAdjustment;

use App\Modules\Attendance\Application\Commands\AttendanceAdjustment\RejectAttendanceAdjustmentCommand;
use App\Modules\Attendance\Domain\Repositories\AttendanceAdjustmentRequestRepositoryInterface;
use Carbon\CarbonImmutable;

class RejectAttendanceAdjustmentHandler
{
    public function __construct(
        private AttendanceAdjustmentRequestRepositoryInterface $adjustmentRepo,
    ) {}

    public function handle(RejectAttendanceAdjustmentCommand $command): void
    {
        $adjustment = $this->adjustmentRepo->findById($command->adjustmentId);
        $adjustment->reject($command->approverId, CarbonImmutable::now());
        $this->adjustmentRepo->saveAndDispatch($adjustment);
    }
}
