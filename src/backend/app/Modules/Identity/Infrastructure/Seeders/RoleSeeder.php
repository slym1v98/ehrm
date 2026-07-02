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
                'description' => 'Manage users, roles, and organization data',
                'permissions' => [
                    'identity.user.list', 'identity.user.view',
                    'identity.role.list', 'identity.role.view',
                    'identity.permission.list',
                    'organization.branch.list', 'organization.branch.view', 'organization.branch.create', 'organization.branch.update',
                    'organization.department.list', 'organization.department.view', 'organization.department.create', 'organization.department.update', 'organization.department.move',
                    'organization.position.list', 'organization.position.view', 'organization.position.create', 'organization.position.update',
                    'organization.tree.view',
                    'employee.view', 'employee.create', 'employee.update', 'employee.status.change',
                    'employee.contract.view', 'employee.contract.create', 'employee.contract.activate', 'employee.contract.renew', 'employee.contract.terminate',
                    'employee.document.view', 'employee.document.upload', 'employee.document.replace', 'employee.document.archive', 'employee.document.download',
                    'shift.template.view', 'shift.template.create', 'shift.template.update',
                    'attendance.raw-log.create', 'attendance.raw-log.view',
                    'attendance.timesheet.view', 'attendance.timesheet.calculate',
                    'attendance.adjustment.create', 'attendance.adjustment.approve',
                    'attendance.period.manage',
                ],
            ],
            'EMPLOYEE' => [
                'name' => 'Employee',
                'description' => 'Self-service only',
                'permissions' => ['organization.tree.view'],
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
