<?php

namespace App\Modules\Attendance\Domain\Events;

use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLogId;
use App\Modules\Attendance\Domain\ValueObjects\EventType;
use Carbon\CarbonImmutable;

final readonly class AttendanceRawLogRecorded
{
    public function __construct(
        public AttendanceRawLogId $rawLogId,
        public string $employeeId,
        public EventType $eventType,
        public CarbonImmutable $eventTime,
    ) {}
}
