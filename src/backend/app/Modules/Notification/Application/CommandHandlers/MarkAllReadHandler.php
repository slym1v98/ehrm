<?php

namespace App\Modules\Notification\Application\CommandHandlers;

use App\Modules\Notification\Application\Commands\MarkAllReadCommand;
use App\Modules\Notification\Domain\Repositories\NotificationMessageRepositoryInterface;
use Carbon\CarbonImmutable;

class MarkAllReadHandler
{
    public function __construct(
        private NotificationMessageRepositoryInterface $messages,
    ) {}

    public function handle(MarkAllReadCommand $command): void
    {
        $this->messages->markAllRead($command->userId, CarbonImmutable::now());
    }
}
