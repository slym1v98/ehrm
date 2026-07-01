<?php

namespace App\Modules\Shift\Domain\Aggregates\ShiftTemplate;

use InvalidArgumentException;

final readonly class OvertimeRules
{
    public function __construct(
        public int $minOvertimeThreshold,
        public int $roundingInterval,
        public int $graceMinutes,
        public int $beforeShiftAllowance,
        public int $afterShiftAllowance,
    ) {
        if ($minOvertimeThreshold < 0 || $roundingInterval < 0 || $graceMinutes < 0 || $beforeShiftAllowance < 0 || $afterShiftAllowance < 0) {
            throw new InvalidArgumentException('OvertimeRules values cannot be negative.');
        }
    }
}
