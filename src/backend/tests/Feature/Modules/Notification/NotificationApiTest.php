<?php

namespace Tests\Feature\Modules\Notification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
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

    public function test_unauthenticated_cannot_list_notifications(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_authenticated_can_list_notifications(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/notifications')->assertStatus(200);
    }

    public function test_unauthenticated_preferences(): void
    {
        $this->putJson('/api/v1/notification-preferences', [
            'channel' => 'in_app',
            'enabled' => true,
        ])->assertStatus(401);
    }

    public function test_admin_can_create_template(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/notification-templates', [
            'code' => 'test.code',
            'name' => 'Test',
            'channel' => 'in_app',
            'subject' => 'Hello {{name}}',
            'body' => 'Body text',
            'variables' => ['name'],
        ])->assertStatus(201);
    }

    public function test_admin_can_list_templates(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/notification-templates')->assertStatus(200);
    }

    public function test_admin_can_update_template(): void
    {
        // Create first
        $create = $this->withToken($this->token)->postJson('/api/v1/notification-templates', [
            'code' => 'update.test',
            'name' => 'Before',
            'channel' => 'email',
            'body' => 'Body',
        ]);
        $id = $create->json('data.id');
        $this->assertNotNull($id);

        $this->withToken($this->token)->patchJson("/api/v1/notification-templates/{$id}", [
            'name' => 'After',
        ])->assertStatus(200);
    }

    public function test_admin_can_activate_deactivate(): void
    {
        $create = $this->withToken($this->token)->postJson('/api/v1/notification-templates', [
            'code' => 'toggle.test',
            'name' => 'Toggle',
            'channel' => 'sms',
            'body' => 'Body',
        ]);
        $id = $create->json('data.id');

        $this->withToken($this->token)->postJson("/api/v1/notification-templates/{$id}/deactivate")->assertStatus(200);
        $this->withToken($this->token)->postJson("/api/v1/notification-templates/{$id}/activate")->assertStatus(200);
    }
    public function test_admin_can_process_outbox(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/notification-outbox/process', [
            'limit' => 5,
        ])->assertStatus(200);
    }

}
