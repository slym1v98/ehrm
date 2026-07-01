<?php

namespace App\Modules\Shift\Domain\Aggregates\ShiftTemplate;

use InvalidArgumentException;

final readonly class ShiftWindow
{
    public bool $isOvernight;

    private function __construct(public string $start, public string $end)
    {
        if (! preg_match('/^\d{2}:\d{2}$/', $start) || ! preg_match('/^\d{2}:\d{2}$/', $end)) {
            throw new InvalidArgumentException('ShiftWindow times must be in HH:MM format.');
        }
        $this->isOvernight = $this->calculateOvernight();
    }

    public static function fromStrings(string $start, string $end): self
    {
        return new self(substr($start, 0, 5), substr($end, 0, 5));
    }

    public function durationMinutes(): int
    {
        $s = explode(':', $this->start);
        $e = explode(':', $this->end);
        $startMin = (int) $s[0] * 60 + (int) $s[1];
        $endMin = (int) $e[0] * 60 + (int) $e[1];
        if ($this->isOvernight) {
            return (1440 - $startMin) + $endMin;
        }
        return $endMin - $startMin;
    }

    private function calculateOvernight(): bool
    {
        $s = explode(':', $this->start);
        $e = explode(':', $this->end);
        return ((int) $s[0] * 60 + (int) $s[1]) > ((int) $e[0] * 60 + (int) $e[1]);
    }
}
