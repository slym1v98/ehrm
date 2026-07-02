<?php

namespace Tests\Feature\Modules\Reporting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingApiTest extends TestCase
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

    public function test_unauthenticated(): void
    {
        $this->getJson('/api/v1/reports')->assertStatus(401);
        $this->getJson('/api/v1/report-runs')->assertStatus(401);
    }

    public function test_list_definitions(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/reports')->assertStatus(200);
    }

    public function test_create_run(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/reports/attendance.summary/runs', [
            'filters' => ['period_id' => '00000000-0000-0000-0000-000000000001'],
        ]);
        $response->assertStatus(201);
    }

    public function test_run_not_found(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/reports/invalid.code/runs')->assertStatus(404);
    }

    public function test_list_runs(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/report-runs')->assertStatus(200);
    }

    public function test_list_definitions_has_seeded(): void
    {
        $response = $this->withToken($this->token)->getJson('/api/v1/reports');
        $data = $response->json('data');
        $codes = array_map(fn($d) => $d['code'], $data);
        $this->assertContains('attendance.summary', $codes);
        $this->assertContains('leave.balance', $codes);
        $this->assertContains('payroll.summary', $codes);
        $this->assertContains('workflow.pending', $codes);
        $this->assertContains('notification.delivery', $codes);
        $this->assertCount(5, $data);
    }
}
