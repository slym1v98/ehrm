<?php

namespace Tests\Unit\Modules\Organization\Domain;

use App\Modules\Organization\Domain\Aggregates\Branch\BranchCode;
use App\Modules\Organization\Domain\Exceptions\InvalidOrganizationCodeException;
use PHPUnit\Framework\TestCase;

class BranchCodeTest extends TestCase
{
    public function test_valid_code(): void
    {
        $code = BranchCode::fromString('HCM-HQ');

        $this->assertSame('HCM-HQ', $code->value);
    }

    public function test_lowercase_is_normalized(): void
    {
        $code = BranchCode::fromString('hcm-hq');

        $this->assertSame('HCM-HQ', $code->value);
    }

    public function test_invalid_code_throws_exception(): void
    {
        $this->expectException(InvalidOrganizationCodeException::class);

        BranchCode::fromString('invalid@code');
    }

    public function test_code_with_space_throws(): void
    {
        $this->expectException(InvalidOrganizationCodeException::class);

        BranchCode::fromString('invalid code');
    }
}
