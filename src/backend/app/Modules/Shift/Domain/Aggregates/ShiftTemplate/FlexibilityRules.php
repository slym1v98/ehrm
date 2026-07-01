<?php

namespace App\Modules\Shift\Domain\Aggregates\ShiftTemplate;

use InvalidArgumentException;

final readonly class FlexibilityRules
{
    public function __construct(
        public int $maxEarlyArrival,
        public int $maxLateDeparture,
        public ?string $coreStart,
        public ?string $coreEnd,
    ) {
        if ($maxEarlyArrival < 0 || $maxLateDeparture < 0) {
            throw new InvalidArgumentException('FlexibilityRules values cannot be negative.');
        }
    }
}
