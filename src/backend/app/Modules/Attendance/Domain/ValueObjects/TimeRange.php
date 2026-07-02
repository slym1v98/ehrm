<?php

namespace App\Modules\Attendance\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class TimeRange
{
    private function __construct(public CarbonImmutable $start, public CarbonImmutable $end)
    {
        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('End time must be after or equal to start time');
        }
    }

    public static function fromTimes(CarbonImmutable $start, CarbonImmutable $end): self
    {
        return new self($start, $end);
    }

    public function durationMinutes(): int
    {
        return $this->start->diffInMinutes($this->end);
    }
}
