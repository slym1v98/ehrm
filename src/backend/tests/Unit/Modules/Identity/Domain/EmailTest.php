<?php

namespace Tests\Unit\Modules\Identity\Domain;

use App\Modules\Identity\Domain\Aggregates\User\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_email_normalizes_to_lowercase(): void
    {
        $this->assertSame('admin@ihrm.local', (string) Email::fromString('Admin@IHRM.Local'));
    }

    public function test_invalid_email_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Email::fromString('bad-email');
    }

    public function test_email_equals(): void
    {
        $a = Email::fromString('a@b.com');
        $b = Email::fromString('A@B.COM');
        $this->assertTrue($a->equals($b));
    }
}
