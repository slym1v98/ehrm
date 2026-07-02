<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

readonly class PayrollFormulaResult
{
    /** @param array<array{component_id: string, category: string, amount: Money, note: ?string}> $lines */
    public function __construct(
        public Money $gross,
        public Money $deduction,
        public Money $net,
        public array $lines,
    ) {}
}
