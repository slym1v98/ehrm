<?php

namespace App\Modules\Notification\Infrastructure\Channels\Contracts;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;

interface NotificationChannelInterface
{
    public function send(MessageTemplate $template, NotificationMessage $message): void;
}
