<?php

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource['id'] ?? $this->resource->id,
            'template_code' => $this->resource['template_code'] ?? $this->resource->template_code,
            'channel' => $this->resource['channel'] ?? $this->resource->channel,
            'subject_rendered' => $this->resource['subject_rendered'] ?? $this->resource->subject_rendered,
            'body_rendered' => $this->resource['body_rendered'] ?? $this->resource->body_rendered,
            'status' => $this->resource['status'] ?? $this->resource->status,
            'read_at' => $this->resource['read_at'] ?? $this->resource->read_at,
            'created_at' => $this->resource['created_at'] ?? $this->resource->created_at,
        ];
    }
}
