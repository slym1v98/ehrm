<?php

namespace App\Modules\Shift\Domain\Aggregates\ShiftAssignment;

use InvalidArgumentException;

final readonly class RecurrenceRule
{
    private const ALLOWED_FREQUENCIES = ['weekly', 'biweekly', 'monthly'];

    public function __construct(
        public string $frequency,
        public int $interval,
        public array $daysOfWeek,
        public ?string $rotationGroup = null,
    ) {
        if (! in_array($frequency, self::ALLOWED_FREQUENCIES, true)) {
            throw new InvalidArgumentException("Invalid recurrence frequency: {$frequency}");
        }
    }
}
