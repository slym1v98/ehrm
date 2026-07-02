<?php

namespace Database\Seeders;

use App\Modules\Configuration\Infrastructure\Seeders\ConfigurationSeeder;
use App\Modules\Identity\Infrastructure\Seeders\AdminUserSeeder;
use App\Modules\Identity\Infrastructure\Seeders\PermissionSeeder;
use App\Modules\Identity\Infrastructure\Seeders\RoleSeeder;
use App\Modules\Leave\Infrastructure\Seeders\LeaveTypeSeeder;
use App\Modules\Payroll\Infrastructure\Seeders\PayrollComponentSeeder;
use App\Modules\Organization\Infrastructure\Seeders\OrgStructureSeeder;
use App\Modules\Notification\Infrastructure\Seeders\NotificationTemplateSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            ConfigurationSeeder::class,
            OrgStructureSeeder::class,
            LeaveTypeSeeder::class,
            PayrollComponentSeeder::class,
            NotificationTemplateSeeder::class,
        ]);
    }
}
