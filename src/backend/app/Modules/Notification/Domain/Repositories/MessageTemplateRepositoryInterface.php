<?php

namespace App\Modules\Notification\Domain\Repositories;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplateId;

interface MessageTemplateRepositoryInterface
{
    public function findById(MessageTemplateId $id): ?MessageTemplate;
    public function findByCode(string $code): ?MessageTemplate;
    /** @return MessageTemplate[] */
    public function list(?string $channel = null, ?bool $active = null): array;
    public function save(MessageTemplate $template): void;
}
