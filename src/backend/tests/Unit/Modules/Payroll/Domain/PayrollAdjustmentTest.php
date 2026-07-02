<?php

namespace Tests\Unit\Modules\Payroll\Domain;

use App\Modules\Payroll\Domain\Aggregates\PayrollAdjustment\{PayrollAdjustment, PayrollAdjustmentId};
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
use App\Modules\Payroll\Domain\Events\PayrollAdjusted;
use App\Modules\Payroll\Domain\ValueObjects\{AdjustmentStatus, Money};
use PHPUnit\Framework\TestCase;

class PayrollAdjustmentTest extends TestCase
{
    private function submit(string $type = 'add', float $amount = 100_000): PayrollAdjustment
    {
        return PayrollAdjustment::submit(
            PayrollAdjustmentId::generate(),
            PayrollEntryId::generate(),
            null,
            $type,
            Money::fromDecimal($amount),
            'test',
            'user-1',
        );
    }

    public function test_submit_creates_pending(): void
    {
        $adj = $this->submit();
        $this->assertSame(AdjustmentStatus::Pending, $adj->getStatus());
    }

    public function test_approve_transitions_and_emits_event(): void
    {
        $adj = $this->submit();
        $event = $adj->approve('mgr-1');
        $this->assertSame(AdjustmentStatus::Approved, $adj->getStatus());
        $this->assertInstanceOf(PayrollAdjusted::class, $event);
    }

    public function test_reject_transitions(): void
    {
        $adj = $this->submit();
        $adj->reject('mgr-1', 'not valid');
        $this->assertSame(AdjustmentStatus::Rejected, $adj->getStatus());
    }

    public function test_cannot_approve_twice(): void
    {
        $adj = $this->submit();
        $adj->approve('m1');
        $this->expectException(\RuntimeException::class);
        $adj->approve('m2');
    }

    public function test_delta_for_add(): void
    {
        $adj = $this->submit('add', 500_000);
        $this->assertEquals(500_000, $adj->getDelta()->toDecimal());
    }

    public function test_delta_for_subtract(): void
    {
        $adj = $this->submit('subtract', 500_000);
        $this->assertEquals(-500_000, $adj->getDelta()->toDecimal());
    }
}
