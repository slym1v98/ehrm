<?php

namespace Tests\Unit\Modules\Shared;

use App\Modules\Shared\Exceptions\ValidationException;
use App\Modules\Shared\Http\Resources\ErrorResource;
use Tests\TestCase;

class ErrorResourceTest extends TestCase
{
    public function test_validation_error_resource_returns_structured_error(): void
    {
        $exception = new ValidationException(
            details: [['field' => 'email', 'message' => 'Required']],
        );

        $result = (new ErrorResource($exception))->toArray(request());

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame('Validation failed', $result['error']['message']);
        $this->assertCount(1, $result['error']['details']);
        $this->assertSame('email', $result['error']['details'][0]['field']);
        $this->assertNotEmpty($result['error']['trace_id']);
    }
}
