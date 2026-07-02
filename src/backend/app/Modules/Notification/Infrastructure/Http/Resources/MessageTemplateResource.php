<?php

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->resource->getId(),
            'code' => $this->resource->getCode(),
            'name' => $this->resource->getName(),
            'channel' => $this->resource->getChannel()->value,
            'subject' => $this->resource->getSubject(),
            'body' => $this->resource->getBody(),
            'variables' => $this->resource->getVariables(),
            'is_active' => $this->resource->isActive(),
            'created_at' => null,
        ];
    }
}
