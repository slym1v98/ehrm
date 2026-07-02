<?php

namespace App\Modules\Notification\Infrastructure\Channels;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;
use App\Modules\Notification\Infrastructure\Channels\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;

class InAppChannel implements NotificationChannelInterface
{
    public function send(MessageTemplate $template, NotificationMessage $message): void
    {
        Log::info('notification.in_app', [
            'message_id' => (string) $message->getId(),
            'recipient_user_id' => $message->getRecipientUserId(),
            'template_code' => $template->getCode(),
        ]);
    }
}
