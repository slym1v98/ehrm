<?php

namespace App\Modules\Audit\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'actor_user_id' => $this->actor_user_id,
            'action' => $this->action,
            'module' => $this->module,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'before_payload' => $this->before_payload,
            'after_payload' => $this->after_payload,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'result' => $this->result,
            'occurred_at' => $this->occurred_at?->toISOString(),
        ];
    }
}
