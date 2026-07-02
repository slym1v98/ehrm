<?php

namespace App\Modules\Notification\Infrastructure\Seeders;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\PermissionModel;
use Illuminate\Database\Seeder;

class NotificationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['notification.view-own', 'view_own', 'View own notifications'],
            ['notification.mark-read-own', 'mark_read_own', 'Mark own notifications read'],
            ['notification.preference.manage-own', 'preference_manage_own', 'Manage own notification preferences'],
            ['notification.template.view', 'template_view', 'View notification templates'],
            ['notification.template.manage', 'template_manage', 'Create/update notification templates'],
            ['notification.outbox.process', 'outbox_process', 'Manually trigger notification outbox processing'],
        ];

        foreach ($permissions as [$code, $display, $desc]) {
            PermissionModel::updateOrCreate(
                ['code' => $code],
                ['display_name' => $display, 'description' => $desc],
            );
        }
    }
}
