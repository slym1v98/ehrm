<?php

namespace App\Modules\Notification\Domain\Repositories;

use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessageId;
use Carbon\CarbonImmutable;

interface NotificationMessageRepositoryInterface
{
    public function findById(NotificationMessageId $id): ?NotificationMessage;
    /** @return NotificationMessage[] */
    public function listForUser(string $userId, ?string $status = null, int $perPage = 15): mixed;
    public function countUnread(string $userId): int;
    public function countUnreadByChannel(string $userId, string $channel): int;
    public function markAllRead(string $userId, CarbonImmutable $at): void;
    public function save(NotificationMessage $message): void;
}
