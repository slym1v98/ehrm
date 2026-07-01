<?php

namespace App\Modules\Identity\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'code' => $this->resource->code,
            'module' => $this->resource->module,
            'action' => $this->resource->action,
            'description' => $this->resource->description,
            'active' => (bool) $this->resource->active,
        ];
    }
}
