<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

use InvalidArgumentException;

final readonly class HashedPassword
{
    private function __construct(public string $value)
    {
    }

    public static function fromHash(string $hash): self
    {
        if ($hash === '') {
            throw new InvalidArgumentException('Hash must not be empty');
        }

        return new self($hash);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
