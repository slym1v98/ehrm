<?php

namespace App\Modules\Notification\Application\CommandHandlers;

use App\Modules\Notification\Application\Commands\ProcessOutboxCommand;
use App\Modules\Notification\Domain\Events\NotificationFailed;
use App\Modules\Notification\Domain\Events\NotificationSent;
use App\Modules\Notification\Domain\Repositories\MessageTemplateRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationMessageRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationOutboxRepositoryInterface;
use App\Modules\Notification\Application\Services\ChannelDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Throwable;

class ProcessOutboxHandler
{
    public function __construct(
        private NotificationOutboxRepositoryInterface $outboxes,
        private NotificationMessageRepositoryInterface $messages,
        private MessageTemplateRepositoryInterface $templates,
        private ChannelDispatcher $dispatcher,
    ) {}

    public function handle(ProcessOutboxCommand $command): array
    {
        $due = $this->outboxes->findDueBatch($command->limit, $command->workerId, new \DateTimeImmutable());

        $processed = 0; $sent = 0; $failed = 0;

        foreach ($due as $outbox) {
            $processed++;

            $outbox->lock($command->workerId, CarbonImmutable::now());
            $this->outboxes->save($outbox);

            try {
                $message = $this->messages->findById(new \App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessageId($outbox->getNotificationMessageId()));
                if ($message === null) {
                    throw new \RuntimeException('Notification message not found for outbox row');
                }

                $template = $this->templates->findByCode($message->getTemplateCode());
                if ($template === null) {
                    throw new \RuntimeException('Template not found for message: '.$message->getTemplateCode());
                }

                $this->dispatcher->dispatch($template, $message);

                $now = CarbonImmutable::now();
                $outbox->succeed($now);
                $message->markSent($now);
                $this->outboxes->save($outbox);
                $this->messages->save($message);

                try {
                    Event::dispatch(new NotificationSent([
                        'message_id' => (string) $message->getId(),
                        'channel' => $message->getChannel()->value,
                    ]));
                } catch (\RuntimeException) {}
                $sent++;
            } catch (Throwable $e) {
                $outbox->fail($e->getMessage());
                $this->outboxes->save($outbox);

                if (isset($message) && $message !== null) {
                    $message->markFailed($e->getMessage());
                    $this->messages->save($message);
                }

                try {
                    Event::dispatch(new NotificationFailed([
                        'outbox_id' => (string) $outbox->getId(),
                        'error' => $e->getMessage(),
                    ]));
                } catch (\RuntimeException) {}
                $failed++;
            }
        }

        return ['processed' => $processed, 'sent' => $sent, 'failed' => $failed];
    }
}
