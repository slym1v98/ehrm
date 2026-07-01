<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

use DateTimeImmutable;

final readonly class DataScopeAssignment
{
    public function __construct(
        public string $id,
        public DataScope $scope,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
