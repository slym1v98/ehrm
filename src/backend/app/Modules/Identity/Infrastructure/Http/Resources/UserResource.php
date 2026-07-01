<?php

namespace App\Modules\Identity\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel $resource
 */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->resource;
        $roles = $user->userRoles()->whereNull('revoked_at')->with('role')->get()
            ->filter(fn ($ur) => $ur->role)
            ->map(fn ($ur) => [
                'id' => $ur->role->id,
                'code' => $ur->role->code,
                'name' => $ur->role->name,
            ])->values()->all();

        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'employee_id' => $user->employee_id,
            'status' => $user->status,
            'last_login_at' => optional($user->last_login_at)->toIso8601String(),
            'roles' => $roles,
            'data_scopes' => $user->dataScopeAssignments->map(fn ($sa) => [
                'id' => $sa->id,
                'scope_type' => $sa->scope_type,
                'branch_id' => $sa->branch_id,
                'department_id' => $sa->department_id,
                'effective_from' => optional($sa->effective_from)->format('Y-m-d'),
                'effective_to' => optional($sa->effective_to)->format('Y-m-d'),
            ])->values()->all(),
        ];
    }
}
