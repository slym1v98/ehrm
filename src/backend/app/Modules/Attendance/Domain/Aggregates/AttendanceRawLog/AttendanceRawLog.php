<?php

namespace App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog;

use App\Modules\Attendance\Domain\Events\AttendanceRawLogRecorded;
use App\Modules\Attendance\Domain\ValueObjects\EventType;
use App\Modules\Attendance\Domain\ValueObjects\GeoPoint;
use App\Modules\Attendance\Domain\ValueObjects\Source;
use Carbon\CarbonImmutable;

class AttendanceRawLog
{
    private array $events = [];

    private function __construct(
        private AttendanceRawLogId $id,
        private string $employeeId,
        private Source $source,
        private EventType $eventType,
        private CarbonImmutable $eventTime,
        private readonly ?GeoPoint $geoPoint,
        private readonly array $payload,
    ) {}

    public static function record(
        string $employeeId,
        Source $source,
        EventType $eventType,
        CarbonImmutable $eventTime,
        ?GeoPoint $geoPoint,
        array $payload,
    ): self {
        $id = AttendanceRawLogId::generate();
        $instance = new self(
            $id, $employeeId, $source, $eventType,
            $eventTime->microsecond(0), $geoPoint, $payload,
        );
        $instance->events[] = new AttendanceRawLogRecorded(
            rawLogId: $id,
            employeeId: $employeeId,
            eventType: $eventType,
            eventTime: $eventTime->microsecond(0),
        );

        return $instance;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    public function employeeId(): string { return $this->employeeId; }
    public function id(): AttendanceRawLogId { return $this->id; }
    public function eventTime(): CarbonImmutable { return $this->eventTime; }
    public function eventType(): EventType { return $this->eventType; }
    public function source(): Source { return $this->source; }
    public function geoPoint(): ?GeoPoint { return $this->geoPoint; }
    public function payload(): array { return $this->payload; }
}
