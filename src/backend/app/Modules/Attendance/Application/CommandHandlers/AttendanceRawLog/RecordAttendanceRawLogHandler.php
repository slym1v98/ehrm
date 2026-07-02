<?php

namespace App\Modules\Attendance\Application\CommandHandlers\AttendanceRawLog;

use App\Modules\Attendance\Application\Commands\AttendanceRawLog\RecordAttendanceRawLogCommand;
use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLog;
use App\Modules\Attendance\Domain\Exceptions\AttendancePeriodClosedException;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;
use App\Modules\Attendance\Domain\Repositories\AttendanceRawLogRepositoryInterface;
use App\Modules\Attendance\Domain\ValueObjects\GeoPoint;
use Carbon\CarbonImmutable;

class RecordAttendanceRawLogHandler
{
    public function __construct(
        private AttendanceRawLogRepositoryInterface $repository,
        private AttendancePeriodRepositoryInterface $periodRepo,
    ) {}

    public function handle(RecordAttendanceRawLogCommand $command): AttendanceRawLog
    {
        $eventDate = CarbonImmutable::parse($command->eventTime)->format('Y-m-d');

        $period = $this->periodRepo->findClosedByDate($eventDate);
        if ($period !== null) {
            throw new AttendancePeriodClosedException("Raw log date {$eventDate}");
        }

        $rawLog = AttendanceRawLog::record(
            employeeId: $command->employeeId,
            source: $command->source,
            eventType: $command->eventType,
            eventTime: CarbonImmutable::parse($command->eventTime),
            geoPoint: $command->geoPoint ? GeoPoint::fromArray($command->geoPoint['lat'], $command->geoPoint['lng']) : null,
            payload: $command->payload,
        );

        $this->repository->saveAndDispatch($rawLog);

        return $rawLog;
    }
}
