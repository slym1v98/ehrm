<?php

namespace App\Modules\Identity\Domain\Events;

use App\Modules\Identity\Domain\Aggregates\User\DataScope;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use DateTimeImmutable;

final readonly class UserDataScopeGranted
{
    public function __construct(public UserId $userId, public DataScope $scope, public DateTimeImmutable $occurredAt) {}
}
