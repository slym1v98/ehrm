<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class DataScope
{
    public function __construct(
        public ScopeType $type,
        public ?string $branchId = null,
        public ?string $departmentId = null,
        public ?DateTimeImmutable $effectiveFrom = null,
        public ?DateTimeImmutable $effectiveTo = null,
    ) {
        if ($this->type === ScopeType::Branch && $this->branchId === null) {
            throw new InvalidArgumentException('Branch scope requires branch_id');
        }
        if ($this->type === ScopeType::Department && $this->departmentId === null) {
            throw new InvalidArgumentException('Department scope requires department_id');
        }
    }
}
