<?php

namespace App\Modules\Notification\Domain\Repositories;

use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutbox;

interface NotificationOutboxRepositoryInterface
{
    /** @return NotificationOutbox[] */
    public function findDueBatch(int $limit, string $workerId, \DateTimeImmutable $now): array;
    public function findById(string $id): ?NotificationOutbox;
    public function save(NotificationOutbox $outbox): void;
}
