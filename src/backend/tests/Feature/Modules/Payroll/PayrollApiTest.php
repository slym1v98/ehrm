<?php

namespace Tests\Feature\Modules\Payroll;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollApiTest extends TestCase
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

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/payroll/periods')->assertStatus(401);
    }

    public function test_list_periods(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/payroll/periods')->assertStatus(200);
    }

    public function test_list_components(): void
    {
        $response = $this->withToken($this->token)->getJson('/api/v1/payroll/components');
        $response->assertStatus(200);
        // Seeded 13 components (base + 7 pass1 + 5 pass2 - dup adjust)
        $this->assertGreaterThanOrEqual(13, count($response->json('data')));
    }

    public function test_create_period(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/payroll/periods', [
            'period_code' => '2026-07',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'cutoff_date' => '2026-07-25',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('payroll_periods', ['period_code' => '2026-07']);
    }

    public function test_start_run_and_lifecycle(): void
    {
        $create = $this->withToken($this->token)->postJson('/api/v1/payroll/periods', [
            'period_code' => '2026-08',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'cutoff_date' => '2026-08-25',
        ]);
        $periodId = $create->json('data.id');

        // Start run (also completes synchronously)
        $this->withToken($this->token)->postJson("/api/v1/payroll/periods/{$periodId}/start-run")
            ->assertStatus(201);

        // Submit for approval
        $this->withToken($this->token)->postJson("/api/v1/payroll/periods/{$periodId}/submit-approval", [
            'workflow_request_id' => $periodId,
        ])->assertStatus(200);

        // Approve
        $this->withToken($this->token)->postJson("/api/v1/payroll/periods/{$periodId}/approve")
            ->assertStatus(200);

        // Lock
        $this->withToken($this->token)->postJson("/api/v1/payroll/periods/{$periodId}/lock")
            ->assertStatus(200);

        // Publish
        $this->withToken($this->token)->postJson("/api/v1/payroll/periods/{$periodId}/publish")
            ->assertStatus(200);

        $this->assertDatabaseHas('payroll_periods', [
            'id' => $periodId,
            'status' => 'published',
        ]);
    }

    public function test_period_code_must_be_unique(): void
    {
        $body = [
            'period_code' => '2026-09',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'cutoff_date' => '2026-09-25',
        ];
        $this->withToken($this->token)->postJson('/api/v1/payroll/periods', $body)->assertStatus(201);
        $this->withToken($this->token)->postJson('/api/v1/payroll/periods', $body)->assertStatus(422);
    }
}
