<?php

namespace App\Modules\Notification\Application\Commands;

readonly class MarkAllReadCommand
{
    public function __construct(public string $userId) {}
}
