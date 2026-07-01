<?php

namespace App\Modules\Identity\Infrastructure\Seeders;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\DataScopeAssignmentModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRoleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('IHRM_ADMIN_EMAIL', 'admin@ihrm.local');
        $password = env('IHRM_ADMIN_PASSWORD', 'password');

        $user = UserModel::updateOrCreate(
            ['email' => $email],
            [
                'id' => (string) Uuid::uuid4(),
                'name' => 'Admin',
                'password' => Hash::make($password),
                'status' => 'active',
            ],
        );

        $superAdmin = RoleModel::where('code', 'SUPER_ADMIN')->first();
        if ($superAdmin && ! UserRoleModel::where('user_id', $user->id)->where('role_id', $superAdmin->id)->whereNull('revoked_at')->exists()) {
            UserRoleModel::create([
                'user_id' => $user->id,
                'role_id' => $superAdmin->id,
                'assigned_by' => null,
                'assigned_at' => now(),
            ]);
        }

        if (! DataScopeAssignmentModel::where('user_id', $user->id)->where('scope_type', 'all_company')->exists()) {
            DataScopeAssignmentModel::create([
                'user_id' => $user->id,
                'scope_type' => 'all_company',
            ]);
        }
    }
}
