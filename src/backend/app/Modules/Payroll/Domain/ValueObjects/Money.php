<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

readonly class Money
{
    public function __construct(
        private int $amount,
        private string $currency = 'VND',
    ) {}

    public static function fromDecimal(float $amount): self
    {
        return new self((int) round($amount * 100));
    }

    public function toDecimal(): float
    {
        return $this->amount / 100;
    }

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function lessThan(self $other): bool
    {
        return $this->amount < $other->amount;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->amount >= $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }
}
