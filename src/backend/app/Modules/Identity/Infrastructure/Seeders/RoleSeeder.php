<?php

namespace App\Modules\Identity\Infrastructure\Seeders;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\PermissionModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RolePermissionModel;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'SUPER_ADMIN' => [
                'name' => 'Super Admin',
                'description' => 'Full access to all modules',
                'permissions' => 'all',
            ],
            'HR_MANAGER' => [
                'name' => 'HR Manager',
                'description' => 'Manage users and roles at read level',
                'permissions' => [
                    'identity.user.list', 'identity.user.view',
                    'identity.role.list', 'identity.role.view',
                    'identity.permission.list',
                ],
            ],
            'EMPLOYEE' => [
                'name' => 'Employee',
                'description' => 'Self-service only',
                'permissions' => [],
            ],
        ];

        $allPermissionCodes = PermissionModel::pluck('code')->all();

        foreach ($roles as $code => $data) {
            $role = RoleModel::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'active' => true,
                ],
            );

            $codes = $data['permissions'] === 'all' ? $allPermissionCodes : $data['permissions'];
            RolePermissionModel::where('role_id', $role->id)->delete();
            foreach ($codes as $permCode) {
                RolePermissionModel::create([
                    'role_id' => $role->id,
                    'permission_code' => $permCode,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
