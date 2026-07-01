<?php

namespace App\Modules\Identity\Application\Services;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RolePermissionModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRoleModel;

class AuthorizationService
{
    public function userHasPermission(string $userId, string $permissionCode): bool
    {
        $roleIds = UserRoleModel::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return false;
        }

        return RolePermissionModel::query()
            ->whereIn('role_id', $roleIds)
            ->where('permission_code', $permissionCode)
            ->exists();
    }

    public function userHasAnyRole(string $userId, array $roleCodes): bool
    {
        return UserRoleModel::query()
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->whereNull('user_roles.revoked_at')
            ->whereIn('roles.code', $roleCodes)
            ->exists();
    }
}
