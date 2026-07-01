<?php

namespace App\Modules\Identity\Infrastructure\Seeders;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\PermissionModel;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['identity.user.list', 'user.list', 'List users'],
            ['identity.user.view', 'user.view', 'View user'],
            ['identity.user.create', 'user.create', 'Create user'],
            ['identity.user.update', 'user.update', 'Update user'],
            ['identity.user.disable', 'user.disable', 'Disable user'],
            ['identity.user.reactivate', 'user.reactivate', 'Reactivate user'],
            ['identity.user.reset_password', 'user.reset_password', 'Reset password'],
            ['identity.user.assign_role', 'user.assign_role', 'Assign role'],
            ['identity.user.revoke_role', 'user.revoke_role', 'Revoke role'],
            ['identity.user.grant_scope', 'user.grant_scope', 'Grant data scope'],
            ['identity.user.revoke_scope', 'user.revoke_scope', 'Revoke data scope'],
            ['identity.role.list', 'role.list', 'List roles'],
            ['identity.role.view', 'role.view', 'View role'],
            ['identity.role.create', 'role.create', 'Create role'],
            ['identity.role.update', 'role.update', 'Update role'],
            ['identity.role.grant_permission', 'role.grant_permission', 'Grant permission to role'],
            ['identity.role.revoke_permission', 'role.revoke_permission', 'Revoke permission from role'],
            ['identity.permission.list', 'permission.list', 'List permissions'],
            ['audit.log.list', 'log.list', 'List audit logs'],
            ['configuration.lookup.list', 'lookup.list', 'List lookup groups'],
            ['configuration.lookup.manage', 'lookup.manage', 'Manage lookup groups'],
            ['configuration.code_generation.list', 'code_generation.list', 'List code generation rules'],
            ['configuration.code_generation.manage', 'code_generation.manage', 'Manage code generation rules'],
            ['configuration.setting.list', 'setting.list', 'List system settings'],
            ['configuration.setting.manage', 'setting.manage', 'Manage system settings'],
            ['configuration.holiday.list', 'holiday.list', 'List holiday calendars'],
            ['configuration.holiday.manage', 'holiday.manage', 'Manage holiday calendars'],
            ['configuration.notification_threshold.list', 'notification_threshold.list', 'List notification thresholds'],
            ['configuration.notification_threshold.manage', 'notification_threshold.manage', 'Manage notification thresholds'],
        ];

        foreach ($permissions as [$code, $action, $description]) {
            PermissionModel::updateOrCreate(
                ['code' => $code],
                [
                    'module' => str($code)->before('.')->toString(),
                    'action' => $action,
                    'description' => $description,
                    'active' => true,
                ],
            );
        }
    }
}
