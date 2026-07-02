<?php

namespace App\Modules\Leave\Domain\ValueObjects;

enum DurationUnit: string
{
    case DAY = 'day';
    case HALF_DAY = 'half_day';
    case HOUR = 'hour';

    public function defaultMinutes(): int
    {
        return match ($this) {
            self::DAY => 480,
            self::HALF_DAY => 240,
            self::HOUR => 60,
        };
    }
}
