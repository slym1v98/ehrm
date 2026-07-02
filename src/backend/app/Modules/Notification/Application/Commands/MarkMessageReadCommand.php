<?php

namespace App\Modules\Notification\Application\Commands;

readonly class MarkMessageReadCommand
{
    public function __construct(
        public string $messageId,
        public string $requesterUserId,
    ) {}
}
