<?php

namespace App\Modules\Notification\Application\Commands;

readonly class ProcessOutboxCommand
{
    public function __construct(
        public int $limit = 50,
        public string $workerId = 'default',
    ) {}
}
