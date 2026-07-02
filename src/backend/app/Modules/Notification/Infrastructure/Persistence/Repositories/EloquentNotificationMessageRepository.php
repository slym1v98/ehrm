<?php

namespace App\Modules\Notification\Infrastructure\Persistence\Repositories;

use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessageId;
use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Repositories\NotificationMessageRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\MessageStatus;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\NotificationMessageModel;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentNotificationMessageRepository implements NotificationMessageRepositoryInterface
{
    public function __construct(private NotificationMessageModel $model) {}

    public function findById(NotificationMessageId $id): ?NotificationMessage
    {
        $record = $this->model->find($id->value());
        return $record ? self::toDomain($record) : null;
    }

    public function listForUser(string $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('recipient_user_id', $userId);
        if ($status !== null) {
            $query->where('status', $status);
        }
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function countUnread(string $userId): int
    {
        return $this->countUnreadByChannel($userId, 'in_app');
    }

    public function countUnreadByChannel(string $userId, string $channel): int
    {
        return $this->model
            ->where('recipient_user_id', $userId)
            ->where('channel', $channel)
            ->whereNull('read_at')
            ->count();
    }

    public function markAllRead(string $userId, CarbonImmutable $at): void
    {
        $this->model
            ->where('recipient_user_id', $userId)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->update(['read_at' => $at->toDateTimeString()]);
    }

    public function save(NotificationMessage $message): void
    {
        $this->model->updateOrCreate(
            ['id' => (string) $message->getId()],
            [
                'template_code' => $message->getTemplateCode(),
                'channel' => $message->getChannel()->value,
                'recipient_user_id' => $message->getRecipientUserId(),
                'recipient_address' => $message->getRecipientAddress(),
                'subject_rendered' => $message->getSubjectRendered(),
                'body_rendered' => $message->getBodyRendered(),
                'payload' => $message->getPayload(),
                'status' => $message->getStatus()->value,
                'priority' => $message->getPriority()->value,
                'error' => $message->getError(),
                'read_at' => $message->getReadAt()?->toDateTimeString(),
                'sent_at' => $message->getSentAt()?->toDateTimeString(),
            ],
        );
    }

    public static function toDomain(NotificationMessageModel $model): NotificationMessage
    {
        return NotificationMessage::reconstitute(
            new NotificationMessageId($model->id),
            $model->template_code,
            Channel::from($model->channel),
            $model->recipient_user_id,
            $model->recipient_address,
            $model->subject_rendered,
            $model->body_rendered,
            $model->payload ?? [],
            MessageStatus::from($model->status),
            NotificationPriority::tryFrom($model->priority ?? 'normal') ?? NotificationPriority::Normal,
            $model->error,
            $model->read_at ? CarbonImmutable::parse($model->read_at) : null,
            $model->sent_at ? CarbonImmutable::parse($model->sent_at) : null,
        );
    }
}
