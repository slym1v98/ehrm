<?php

namespace App\Modules\Notification\Domain\Exceptions;

use RuntimeException;

class ChannelDeliveryFailedException extends RuntimeException
{
    public function __construct(string $channel, string $reason)
    {
        parent::__construct("Channel [{$channel}] delivery failed: {$reason}");
    }
}
