<?php

namespace App\Modules\Leave\Domain\ValueObjects;

use Carbon\CarbonImmutable;

class LeavePeriod
{
    public function __construct(
        private CarbonImmutable $startAt,
        private CarbonImmutable $endAt,
        private DurationUnit $durationUnit,
        private int $durationMinutes,
    ) {
        if ($endAt->lessThan($startAt)) {
            throw new \InvalidArgumentException('endAt must be >= startAt');
        }
    }

    public function startAt(): CarbonImmutable { return $this->startAt; }
    public function endAt(): CarbonImmutable { return $this->endAt; }
    public function durationUnit(): DurationUnit { return $this->durationUnit; }
    public function durationMinutes(): int { return $this->durationMinutes; }

    public function overlaps(self $other): bool
    {
        return $this->startAt->lessThanOrEqualTo($other->endAt)
            && $this->endAt->greaterThanOrEqualTo($other->startAt);
    }
}
