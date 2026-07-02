<?php

namespace App\Modules\Leave\Domain\Aggregates\LeavePolicy;

use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\ValueObjects\DurationUnit;
use Carbon\CarbonImmutable;

class LeavePolicy
{
    public function __construct(
        private LeavePolicyId $id,
        private LeaveTypeId $leaveTypeId,
        private CarbonImmutable $validFrom,
        private ?CarbonImmutable $validUntil,
        private ?int $maxConsecutiveDays,
        private bool $requiresAttachment,
        private ?int $carryOverLimit,
        private ?int $carryOverExpiryMonths,
        private bool $halfDayAllowed,
        private bool $hourlyAllowed,
    ) {}

    public function id(): LeavePolicyId { return $this->id; }
    public function leaveTypeId(): LeaveTypeId { return $this->leaveTypeId; }
    public function validFrom(): CarbonImmutable { return $this->validFrom; }
    public function validUntil(): ?CarbonImmutable { return $this->validUntil; }
    public function maxConsecutiveDays(): ?int { return $this->maxConsecutiveDays; }
    public function requiresAttachment(): bool { return $this->requiresAttachment; }
    public function carryOverLimit(): ?int { return $this->carryOverLimit; }
    public function carryOverExpiryMonths(): ?int { return $this->carryOverExpiryMonths; }
    public function halfDayAllowed(): bool { return $this->halfDayAllowed; }
    public function hourlyAllowed(): bool { return $this->hourlyAllowed; }
    public function isValidForDate(CarbonImmutable $date): bool { return $date->gte($this->validFrom) && ($this->validUntil === null || $date->lte($this->validUntil)); }
    public function allowsDuration(DurationUnit $unit): bool { return match ($unit) { DurationUnit::DAY => true, DurationUnit::HALF_DAY => $this->halfDayAllowed, DurationUnit::HOUR => $this->hourlyAllowed }; }
}
