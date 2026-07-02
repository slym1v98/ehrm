<?php

namespace App\Modules\Leave\Domain\Aggregates\LeaveType;

class LeaveType
{
    public function __construct(
        private LeaveTypeId $id,
        private string $code,
        private string $name,
        private bool $isBalanceTracked,
        private bool $isActive = true,
        private int $sortOrder = 0,
    ) {}

    public function id(): LeaveTypeId { return $this->id; }
    public function code(): string { return $this->code; }
    public function name(): string { return $this->name; }
    public function isBalanceTracked(): bool { return $this->isBalanceTracked; }
    public function isActive(): bool { return $this->isActive; }
    public function sortOrder(): int { return $this->sortOrder; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
