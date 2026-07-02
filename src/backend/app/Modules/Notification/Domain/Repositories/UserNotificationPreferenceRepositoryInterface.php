<?php

namespace App\Modules\Notification\Domain\Repositories;

use App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreference;
use App\Modules\Notification\Domain\ValueObjects\Channel;

interface UserNotificationPreferenceRepositoryInterface
{
    public function findByUserAndChannel(string $userId, Channel $channel, ?string $templateCode = null): ?UserNotificationPreference;
    /** @return UserNotificationPreference[] */
    public function listByUser(string $userId): array;
    public function save(UserNotificationPreference $preference): void;
}
