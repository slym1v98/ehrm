<?php

namespace App\Modules\Notification\Domain\Events;

class NotificationSent
{
    public function __construct(public readonly array $payload) {}
}
