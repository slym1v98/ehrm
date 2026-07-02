<?php

namespace App\Modules\Notification\Domain\Services;

use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;

interface NotificationPublisher
{
    public function send(
        string $templateCode,
        string $recipientUserId,
        Channel $channel,
        array $payload,
        NotificationPriority $priority = NotificationPriority::Normal,
        ?string $recipientAddress = null,
    ): void;
}
