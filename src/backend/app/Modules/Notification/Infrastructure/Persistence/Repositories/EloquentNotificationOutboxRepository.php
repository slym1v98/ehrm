<?php

namespace App\Modules\Notification\Infrastructure\Persistence\Repositories;

use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutbox;
use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutboxId;
use App\Modules\Notification\Domain\Repositories\NotificationOutboxRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\OutboxStatus;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\NotificationOutboxModel;
use Carbon\CarbonImmutable;

class EloquentNotificationOutboxRepository implements NotificationOutboxRepositoryInterface
{
    public function __construct(private NotificationOutboxModel $model) {}

    public function findDueBatch(int $limit, string $workerId, \DateTimeImmutable $now): array
    {
        $records = $this->model
            ->whereIn('status', [OutboxStatus::Pending->value, OutboxStatus::Failed->value])
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where('available_at', '<=', $now->format('Y-m-d H:i:s'))
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        return $records->map(fn($r) => self::toDomain($r))->all();
    }

    public function findById(string $id): ?NotificationOutbox
    {
        $record = $this->model->find($id);
        return $record ? self::toDomain($record) : null;
    }

    public function save(NotificationOutbox $outbox): void
    {
        $this->model->updateOrCreate(
            ['id' => (string) $outbox->getId()],
            [
                'notification_message_id' => $outbox->getNotificationMessageId(),
                'channel' => $outbox->getChannel()->value,
                'status' => $outbox->getStatus()->value,
                'attempts' => $outbox->getAttempts(),
                'max_attempts' => $outbox->getMaxAttempts(),
                'available_at' => $outbox->getAvailableAt()->toDateTimeString(),
                'locked_at' => $outbox->getLockedAt()?->toDateTimeString(),
                'locked_by' => $outbox->getLockedBy(),
                'last_error' => $outbox->getLastError(),
            ],
        );
    }

    public static function toDomain(NotificationOutboxModel $model): NotificationOutbox
    {
        return NotificationOutbox::reconstitute(
            new NotificationOutboxId($model->id),
            $model->notification_message_id,
            Channel::from($model->channel),
            OutboxStatus::from($model->status),
            $model->attempts,
            $model->max_attempts,
            CarbonImmutable::parse($model->available_at),
            $model->locked_at ? CarbonImmutable::parse($model->locked_at) : null,
            $model->locked_by,
            $model->last_error,
        );
    }
}
