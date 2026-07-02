<?php

namespace App\Modules\Notification\Application;

use App\Modules\Notification\Application\CommandHandlers\SendNotificationHandler;
use App\Modules\Notification\Application\Commands\SendNotificationCommand;
use App\Modules\Notification\Domain\Services\NotificationPublisher;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;

class NotificationPublisherService implements NotificationPublisher
{
    public function __construct(private SendNotificationHandler $handler) {}

    public function send(
        string $templateCode,
        string $recipientUserId,
        Channel $channel,
        array $payload,
        NotificationPriority $priority = NotificationPriority::Normal,
        ?string $recipientAddress = null,
    ): void {
        $this->handler->handle(new SendNotificationCommand(
            $templateCode, $recipientUserId, $channel, $payload, $priority, $recipientAddress,
        ));
    }
}
