<?php

namespace App\Modules\Notification\Application\Services;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;
use App\Modules\Notification\Infrastructure\Channels\Contracts\NotificationChannelInterface;
use Illuminate\Contracts\Container\Container;

class ChannelDispatcher
{
    public function __construct(private Container $container) {}

    public function dispatch(MessageTemplate $template, NotificationMessage $message): void
    {
        $adapter = $this->container->make(NotificationChannelInterface::class . ':' . $template->getChannel()->value);
        $adapter->send($template, $message);
    }
}
