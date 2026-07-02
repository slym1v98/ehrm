<?php

namespace App\Modules\Notification\Domain\Events;

class NotificationFailed
{
    public function __construct(public readonly array $payload) {}
}
