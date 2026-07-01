<?php

namespace App\Modules\Identity\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        $role = $this->resource;

        return [
            'id' => $role->id,
            'code' => $role->code,
            'name' => $role->name,
            'description' => $role->description,
            'active' => (bool) $role->active,
            'permissions' => $role->rolePermissions->map(fn ($rp) => $rp->permission_code)->values()->all(),
        ];
    }
}
