<?php

namespace App\Modules\Notification\Application\CommandHandlers;

use App\Modules\Notification\Application\Commands\SendNotificationCommand;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessageId;
use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutbox;
use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutboxId;
use App\Modules\Notification\Domain\Events\NotificationQueued;
use App\Modules\Notification\Domain\Exceptions\MessageTemplateNotFoundException;
use App\Modules\Notification\Domain\Repositories\MessageTemplateRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationMessageRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationOutboxRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\UserNotificationPreferenceRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;
use Illuminate\Support\Facades\Event;

class SendNotificationHandler
{
    public function __construct(
        private MessageTemplateRepositoryInterface $templates,
        private NotificationMessageRepositoryInterface $messages,
        private NotificationOutboxRepositoryInterface $outboxes,
        private UserNotificationPreferenceRepositoryInterface $preferences,
    ) {}

    public function handle(SendNotificationCommand $command): ?NotificationMessage
    {
        $template = $this->templates->findByCode($command->templateCode);
        if ($template === null) {
            throw new MessageTemplateNotFoundException($command->templateCode);
        }
        if (! $template->isActive()) {
            return null;
        }
        if ($template->getChannel() !== $command->channel) {
            return null;
        }

        if ($command->priority !== NotificationPriority::High) {
            $pref = $this->preferences->findByUserAndChannel(
                $command->recipientUserId,
                $command->channel,
                $command->templateCode,
            ) ?? $this->preferences->findByUserAndChannel(
                $command->recipientUserId,
                $command->channel,
                null,
            );

            if ($pref !== null && ! $pref->isEnabled()) {
                return null;
            }
        }

        $rendered = $template->render($command->payload);

        $message = NotificationMessage::create(
            NotificationMessageId::generate(),
            $template,
            $command->recipientUserId,
            $command->recipientAddress,
            $rendered,
            $command->payload,
            $command->priority,
        );
        $outbox = NotificationOutbox::create(NotificationOutboxId::generate(), $message);

        $this->messages->save($message);
        $this->outboxes->save($outbox);

        try {
            Event::dispatch(new NotificationQueued([
                'message_id' => (string) $message->getId(),
                'template_code' => $command->templateCode,
                'channel' => $command->channel->value,
                'recipient_user_id' => $command->recipientUserId,
            ]));
        } catch (\RuntimeException) {}

        return $message;
    }
}
