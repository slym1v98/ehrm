<?php

namespace App\Modules\Attendance\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class TimesheetData
{
    public function __construct(
        public int $expectedMinutes,
        public int $workedMinutes,
        public int $lateMinutes,
        public int $earlyLeaveMinutes,
        public int $overtimeMinutes,
        public AttendanceStatus $status,
    ) {
        foreach ([
            'expectedMinutes' => $expectedMinutes,
            'workedMinutes' => $workedMinutes,
            'lateMinutes' => $lateMinutes,
            'earlyLeaveMinutes' => $earlyLeaveMinutes,
            'overtimeMinutes' => $overtimeMinutes,
        ] as $name => $value) {
            if ($value < 0) {
                throw new InvalidArgumentException("{$name} must be non-negative");
            }
        }
    }
}
