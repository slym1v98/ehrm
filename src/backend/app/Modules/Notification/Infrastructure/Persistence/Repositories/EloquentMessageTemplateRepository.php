<?php

namespace App\Modules\Notification\Infrastructure\Persistence\Repositories;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplateId;
use App\Modules\Notification\Domain\Repositories\MessageTemplateRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\MessageTemplateModel;

class EloquentMessageTemplateRepository implements MessageTemplateRepositoryInterface
{
    public function __construct(private MessageTemplateModel $model) {}

    public function findById(MessageTemplateId $id): ?MessageTemplate
    {
        $record = $this->model->find($id->value());
        return $record ? self::toDomain($record) : null;
    }

    public function findByCode(string $code): ?MessageTemplate
    {
        $record = $this->model->where('code', $code)->first();
        return $record ? self::toDomain($record) : null;
    }

    public function list(?string $channel = null, ?bool $active = null): array
    {
        $query = $this->model->query();
        if ($channel !== null) {
            $query->where('channel', $channel);
        }
        if ($active !== null) {
            $query->where('is_active', $active);
        }
        return $query->get()->map(fn($r) => self::toDomain($r))->all();
    }

    public function save(MessageTemplate $template): void
    {
        $this->model->updateOrCreate(
            ['id' => (string) $template->getId()],
            [
                'code' => $template->getCode(),
                'name' => $template->getName(),
                'channel' => $template->getChannel()->value,
                'subject' => $template->getSubject(),
                'body' => $template->getBody(),
                'variables' => $template->getVariables(),
                'is_active' => $template->isActive(),
            ],
        );
    }

    public static function toDomain(MessageTemplateModel $model): MessageTemplate
    {
        return MessageTemplate::create(
            new MessageTemplateId($model->id),
            $model->code,
            $model->name,
            Channel::from($model->channel),
            $model->subject ?? '',
            $model->body,
            $model->variables ?? [],
            $model->is_active,
        );
    }
}
