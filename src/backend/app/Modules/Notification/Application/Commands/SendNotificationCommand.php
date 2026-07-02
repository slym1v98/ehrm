<?php

namespace App\Modules\Notification\Application\Commands;

use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;

readonly class SendNotificationCommand
{
    public function __construct(
        public string $templateCode,
        public string $recipientUserId,
        public Channel $channel,
        public array $payload = [],
        public NotificationPriority $priority = NotificationPriority::Normal,
        public ?string $recipientAddress = null,
    ) {}
}
