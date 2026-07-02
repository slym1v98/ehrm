<?php

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserNotificationPreferenceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->resource->getId(),
            'channel' => $this->resource->getChannel()->value,
            'template_code' => $this->resource->getTemplateCode(),
            'enabled' => $this->resource->isEnabled(),
        ];
    }
}
