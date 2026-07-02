<?php

namespace Tests\Feature\Modules\Attendance;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
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
        $this->getJson('/api/v1/attendance/raw-logs')->assertStatus(401);
    }

    public function test_list_raw_logs_authorized(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/attendance/raw-logs')->assertStatus(200);
    }

    public function test_create_raw_log(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/attendance/raw-logs', [
            'employee_id' => (string) UserModel::query()->value('id'),
            'source' => 'web',
            'event_type' => 'check_in',
            'event_time' => '2026-07-02T08:00:00+07:00',
            'payload' => [],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('attendance_raw_logs', 1);
    }

    public function test_open_and_list_periods(): void
    {
        $create = $this->withToken($this->token)->postJson('/api/v1/attendance-periods', [
            'period_code' => '2026-07',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $create->assertStatus(201);
        $this->withToken($this->token)->getJson('/api/v1/attendance-periods')->assertStatus(200);
    }

    public function test_reopen_requires_reason(): void
    {
        $create = $this->withToken($this->token)->postJson('/api/v1/attendance-periods', [
            'period_code' => '2026-08',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);

        $periodId = $create->json('data.id');
        $this->withToken($this->token)->postJson("/api/v1/attendance-periods/{$periodId}/close")->assertStatus(200);
        $this->withToken($this->token)->postJson("/api/v1/attendance-periods/{$periodId}/reopen", [])->assertStatus(422);
    }

    public function test_submit_adjustment_and_list_pending(): void
    {
        $timesheetId = (string) \Illuminate\Support\Str::uuid();

        \DB::table('attendance_periods')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'period_code' => '2026-09',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $periodId = \DB::table('attendance_periods')->value('id');

        \DB::table('attendance_timesheets')->insert([
            'id' => $timesheetId,
            'attendance_period_id' => $periodId,
            'employee_id' => (string) UserModel::query()->value('id'),
            'work_date' => '2026-09-02',
            'shift_assignment_id' => null,
            'expected_minutes' => 480,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'result_status' => 'absent',
            'calculation_run_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($this->token)->postJson('/api/v1/attendance-adjustment-requests', [
            'attendance_timesheet_id' => $timesheetId,
            'employee_id' => (string) UserModel::query()->value('id'),
            'corrections' => ['check_in' => '2026-09-02T08:05:00+07:00'],
            'reason' => 'Forgot check in',
        ])->assertStatus(201);

        $this->withToken($this->token)->getJson('/api/v1/attendance-adjustment-requests')->assertStatus(200);
    }


    public function test_raw_log_blocked_by_closed_period(): void
    {
        // Create then close period
        $r = $this->withToken($this->token)->postJson('/api/v1/attendance-periods', [
            'period_code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);
        $periodId = $r->json('data.id');
        $this->withToken($this->token)->postJson("/api/v1/attendance-periods/{$periodId}/close")->assertStatus(200);

        $this->withToken($this->token)->postJson('/api/v1/attendance/raw-logs', [
            'employee_id' => (string) UserModel::query()->value('id'),
            'source' => 'web',
            'event_type' => 'check_in',
            'event_time' => '2026-11-15T08:00:00+07:00',
            'payload' => [],
        ])->assertStatus(422);
    }

    public function test_adjustment_blocked_by_closed_period(): void
    {
        $timesheetId = (string) \Illuminate\Support\Str::uuid();

        \DB::table('attendance_periods')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'period_code' => '2026-12',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
            'status' => 'closed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $periodId = \DB::table('attendance_periods')->value('id');

        \DB::table('attendance_timesheets')->insert([
            'id' => $timesheetId,
            'attendance_period_id' => $periodId,
            'employee_id' => (string) UserModel::query()->value('id'),
            'work_date' => '2026-12-02',
            'shift_assignment_id' => null,
            'expected_minutes' => 480,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'result_status' => 'absent',
            'calculation_run_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($this->token)->postJson('/api/v1/attendance-adjustment-requests', [
            'attendance_timesheet_id' => $timesheetId,
            'employee_id' => (string) UserModel::query()->value('id'),
            'corrections' => ['check_in' => '2026-12-02T08:05:00+07:00'],
            'reason' => 'Forgot check in',
        ])->assertStatus(422);
    }

    public function test_duplicate_pending_adjustment_returns_409(): void
    {
        $timesheetId = (string) \Illuminate\Support\Str::uuid();

        \DB::table('attendance_periods')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'period_code' => '2026-10',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $periodId = \DB::table('attendance_periods')->value('id');

        \DB::table('attendance_timesheets')->insert([
            'id' => $timesheetId,
            'attendance_period_id' => $periodId,
            'employee_id' => (string) UserModel::query()->value('id'),
            'work_date' => '2026-10-02',
            'shift_assignment_id' => null,
            'expected_minutes' => 480,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'result_status' => 'absent',
            'calculation_run_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'attendance_timesheet_id' => $timesheetId,
            'employee_id' => (string) UserModel::query()->value('id'),
            'corrections' => ['check_in' => '2026-10-02T08:05:00+07:00'],
            'reason' => 'Forgot check in',
        ];

        $this->withToken($this->token)->postJson('/api/v1/attendance-adjustment-requests', $payload)->assertStatus(201);
        $this->withToken($this->token)->postJson('/api/v1/attendance-adjustment-requests', $payload)->assertStatus(409);
    }
}
