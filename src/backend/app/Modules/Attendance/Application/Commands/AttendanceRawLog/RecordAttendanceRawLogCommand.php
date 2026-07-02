<?php

namespace App\Modules\Attendance\Application\Commands\AttendanceRawLog;

use App\Modules\Attendance\Domain\ValueObjects\EventType;
use App\Modules\Attendance\Domain\ValueObjects\Source;

final readonly class RecordAttendanceRawLogCommand
{
    public function __construct(
        public string $employeeId,
        public Source $source,
        public EventType $eventType,
        public string $eventTime,
        public ?array $geoPoint,
        public array $payload,
    ) {}
}
