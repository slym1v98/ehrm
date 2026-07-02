<?php

namespace App\Modules\Attendance\Domain\Aggregates\AttendancePeriod;

use App\Modules\Attendance\Domain\Events\AttendancePeriodClosed;
use App\Modules\Attendance\Domain\Events\AttendancePeriodOpened;
use App\Modules\Attendance\Domain\Events\AttendancePeriodReopened;
use App\Modules\Attendance\Domain\ValueObjects\PeriodStatus;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class AttendancePeriod
{
    private array $events = [];

    private function __construct(
        private AttendancePeriodId $id,
        private string $periodCode,
        private CarbonImmutable $startDate,
        private CarbonImmutable $endDate,
        private PeriodStatus $status,
    ) {}

    public static function open(
        string $periodCode,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): self {
        if ($startDate->greaterThan($endDate)) {
            throw new InvalidArgumentException('Start date must be before or equal to end date');
        }

        $id = AttendancePeriodId::generate();
        $instance = new self($id, $periodCode, $startDate, $endDate, PeriodStatus::Open);
        $instance->events[] = new AttendancePeriodOpened(periodId: $id, periodCode: $periodCode);

        return $instance;
    }

    public static function reconstitute(
        AttendancePeriodId $id,
        string $periodCode,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        PeriodStatus $status,
    ): self {
        return new self($id, $periodCode, $startDate, $endDate, $status);
    }

    public function close(): void
    {
        $this->status = PeriodStatus::Closed;
        $this->events[] = new AttendancePeriodClosed(periodId: $this->id, periodCode: $this->periodCode);
    }

    public function reopen(string $reason): void
    {
        if ($reason === '') {
            throw new InvalidArgumentException('Reopen reason must not be empty');
        }

        $this->status = PeriodStatus::Reopened;
        $this->events[] = new AttendancePeriodReopened(periodId: $this->id, reason: $reason);
    }

    public function isClosed(): bool
    {
        return $this->status === PeriodStatus::Closed;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    public function id(): AttendancePeriodId { return $this->id; }
    public function periodCode(): string { return $this->periodCode; }
    public function startDate(): CarbonImmutable { return $this->startDate; }
    public function endDate(): CarbonImmutable { return $this->endDate; }
    public function status(): PeriodStatus { return $this->status; }
}
