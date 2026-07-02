<?php

namespace App\Modules\Notification\Domain\Events;

class NotificationQueued
{
    public function __construct(public readonly array $payload) {}
}
