<?php

namespace App\Modules\Leave\Domain\Aggregates\LeaveBalance;

use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Events\LeaveBalanceAdjusted;
use App\Modules\Leave\Domain\Exceptions\InsufficientBalanceException;

class LeaveBalance
{
    public function __construct(private LeaveBalanceId $id, private string $employeeId, private LeaveTypeId $leaveTypeId, private int $year, private int $opening = 0, private int $accrued = 0, private int $used = 0, private int $carriedOver = 0, private int $expired = 0) {}
    public function id(): LeaveBalanceId { return $this->id; }
    public function employeeId(): string { return $this->employeeId; }
    public function leaveTypeId(): LeaveTypeId { return $this->leaveTypeId; }
    public function year(): int { return $this->year; }
    public function opening(): int { return $this->opening; }
    public function accrued(): int { return $this->accrued; }
    public function used(): int { return $this->used; }
    public function carriedOver(): int { return $this->carriedOver; }
    public function expired(): int { return $this->expired; }
    public function remaining(): int { return $this->opening + $this->accrued - $this->used - $this->expired - $this->carriedOver; }
    public function deduct(int $minutes): LeaveBalanceAdjusted { if ($this->remaining() < $minutes) throw new InsufficientBalanceException('Insufficient leave balance'); $old=$this->used; $this->used += $minutes; return new LeaveBalanceAdjusted(['balance_id'=>$this->id->value(),'previous_used'=>$old,'new_used'=>$this->used]); }
    public function restore(int $minutes): LeaveBalanceAdjusted { $old=$this->used; $this->used = max(0, $this->used - $minutes); return new LeaveBalanceAdjusted(['balance_id'=>$this->id->value(),'previous_used'=>$old,'new_used'=>$this->used]); }
}
