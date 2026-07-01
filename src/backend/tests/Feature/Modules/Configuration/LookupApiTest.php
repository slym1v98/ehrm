<?php

namespace Tests\Feature\Modules\Configuration;

use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\LookupGroupModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use App\Modules\Identity\Infrastructure\Seeders\AdminUserSeeder;
use App\Modules\Identity\Infrastructure\Seeders\PermissionSeeder;
use App\Modules\Identity\Infrastructure\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedAdmin(): UserModel
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AdminUserSeeder::class);
        return UserModel::where('email', 'admin@ihrm.local')->firstOrFail();
    }

    public function test_list_lookups_requires_permission(): void
    {
        $user = UserModel::create([
            'name' => 'No Permission',
            'email' => 'no-permission@ihrm.local',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/config/lookups')
            ->assertStatus(403);
    }

    public function test_list_lookups_returns_paginated_groups(): void
    {
        $admin = $this->seedAdmin();

        LookupGroupModel::create(['code' => 'gender', 'name' => 'Gender', 'active' => true]);
        LookupGroupModel::create(['code' => 'status', 'name' => 'Status', 'active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/config/lookups')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'active']], 'meta' => ['total']]);
    }

    public function test_create_lookup_group(): void
    {
        $admin = $this->seedAdmin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/config/lookups', [
                'code' => 'marital_status',
                'name' => 'Marital Status',
                'description' => 'Employee marital status',
                'active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'marital_status');
    }

    public function test_show_lookup_group(): void
    {
        $admin = $this->seedAdmin();
        $group = LookupGroupModel::create(['code' => 'gender', 'name' => 'Gender', 'active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/config/lookups/' . $group->id)
            ->assertOk()
            ->assertJsonPath('data.code', 'gender');
    }

    public function test_add_value_to_group(): void
    {
        $admin = $this->seedAdmin();
        $group = LookupGroupModel::create(['code' => 'gender', 'name' => 'Gender', 'active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/config/lookups/{$group->id}/values", [
                'code' => 'M',
                'name' => 'Male',
                'sort_order' => 1,
                'active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'M');
    }
}
