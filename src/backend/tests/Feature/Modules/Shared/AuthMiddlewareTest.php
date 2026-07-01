<?php

namespace Tests\Feature\Modules\Shared;

use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'trace_id'],
        ]);
    }
}
