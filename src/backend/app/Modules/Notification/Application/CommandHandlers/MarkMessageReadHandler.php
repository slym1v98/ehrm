<?php

namespace App\Modules\Notification\Application\CommandHandlers;

use App\Modules\Notification\Application\Commands\MarkMessageReadCommand;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessageId;
use App\Modules\Notification\Domain\Exceptions\NotificationMessageNotFoundException;
use App\Modules\Notification\Domain\Repositories\NotificationMessageRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use Carbon\CarbonImmutable;

class MarkMessageReadHandler
{
    public function __construct(
        private NotificationMessageRepositoryInterface $messages,
    ) {}

    public function handle(MarkMessageReadCommand $command): void
    {
        $message = $this->messages->findById(new NotificationMessageId($command->messageId));
        if ($message === null) {
            throw new NotificationMessageNotFoundException($command->messageId);
        }
        if ($message->getRecipientUserId() !== $command->requesterUserId) {
            throw new NotificationMessageNotFoundException($command->messageId);
        }
        if ($message->getChannel() !== Channel::InApp) {
            throw new \InvalidArgumentException('Only in_app messages can be marked read');
        }
        if ($message->getReadAt() === null) {
            $message->markRead(CarbonImmutable::now());
            $this->messages->save($message);
        }
    }
}
