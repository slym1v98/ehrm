<?php

namespace Tests\Feature\Modules\Shift;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@ihrm.local',
            'password' => 'password',
        ]);
        $this->token = $response->json('data.access_token');
    }

    public function test_list_templates(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/shift-templates')->assertStatus(200);
    }

    public function test_create_template(): void
    {
        $r = $this->withToken($this->token)->postJson('/api/v1/shift-templates', [
            'code' => 'DAY',
            'name' => 'Day Shift',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'break_minutes' => 60,
            'late_tolerance_minutes' => 5,
        ]);
        $r->assertStatus(201);
        $this->assertDatabaseHas('shift_templates', ['code' => 'DAY']);
    }

    public function test_create_overnight_template_without_attribution_422(): void
    {
        $r = $this->withToken($this->token)->postJson('/api/v1/shift-templates', [
            'code' => 'NIGHT',
            'name' => 'Night Shift',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'break_minutes' => 60,
        ]);
        $r->assertStatus(422);
    }

    public function test_assign_shift(): void
    {
        $tpl = $this->withToken($this->token)->postJson('/api/v1/shift-templates', [
            'code' => 'DAY2',
            'name' => 'Day',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ])->json('data');

        $r = $this->withToken($this->token)->postJson('/api/v1/shift-assignments', [
            'shift_template_id' => $tpl['id'],
            'assignable_type' => 'employee',
            'assignable_id' => '00000000-0000-4000-8000-000000000001',
            'effective_from' => '2026-07-01',
        ]);
        $r->assertStatus(201);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/shift-templates')->assertStatus(401);
    }

    public function test_unauthorized_role_returns_403(): void
    {
        $user = UserModel::create([
            'name' => 'No Permission',
            'email' => 'no-permission@ihrm.local',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shift-templates', [
                'code' => 'FORBIDDEN',
                'name' => 'x',
                'start_time' => '08:00',
                'end_time' => '17:00',
            ])->assertStatus(403);
    }
}
