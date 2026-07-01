<?php

namespace App\Modules\Shift\Application\Commands\ShiftTemplate;

final readonly class CreateShiftTemplateCommand
{
    public function __construct(
        public string $code,
        public string $name,
        public string $startTime,
        public string $endTime,
        public int $breakMinutes,
        public int $lateToleranceMinutes,
        public ?array $overtimeRules = null,
        public ?array $flexibilityRules = null,
        public ?string $payrollAttributionRule = null,
    ) {}
}
