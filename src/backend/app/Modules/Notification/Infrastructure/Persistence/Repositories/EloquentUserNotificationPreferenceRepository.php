<?php

namespace App\Modules\Notification\Infrastructure\Persistence\Repositories;

use App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreference;
use App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreferenceId;
use App\Modules\Notification\Domain\Repositories\UserNotificationPreferenceRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\UserNotificationPreferenceModel;

class EloquentUserNotificationPreferenceRepository implements UserNotificationPreferenceRepositoryInterface
{
    public function __construct(private UserNotificationPreferenceModel $model) {}

    public function findByUserAndChannel(string $userId, Channel $channel, ?string $templateCode = null): ?UserNotificationPreference
    {
        $query = $this->model
            ->where('user_id', $userId)
            ->where('channel', $channel->value);

        if ($templateCode !== null) {
            $query->where('template_code', $templateCode);
        } else {
            $query->whereNull('template_code');
        }

        $record = $query->first();
        return $record ? self::toDomain($record) : null;
    }

    public function listByUser(string $userId): array
    {
        return $this->model
            ->where('user_id', $userId)
            ->get()
            ->map(fn($r) => self::toDomain($r))
            ->all();
    }

    public function save(UserNotificationPreference $preference): void
    {
        $this->model->updateOrCreate(
            ['id' => (string) $preference->getId()],
            [
                'user_id' => $preference->getUserId(),
                'channel' => $preference->getChannel()->value,
                'template_code' => $preference->getTemplateCode(),
                'enabled' => $preference->isEnabled(),
            ],
        );
    }

    public static function toDomain(UserNotificationPreferenceModel $model): UserNotificationPreference
    {
        return UserNotificationPreference::set(
            new UserNotificationPreferenceId($model->id),
            $model->user_id,
            Channel::from($model->channel),
            $model->template_code,
            $model->enabled,
        );
    }
}
