<?php
namespace Tests\Unit\Modules\Leave\Domain;
use App\Modules\Leave\Domain\Aggregates\LeaveBalance\LeaveBalance;
use App\Modules\Leave\Domain\Aggregates\LeaveBalance\LeaveBalanceId;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Events\LeaveBalanceAdjusted;
use App\Modules\Leave\Domain\Exceptions\InsufficientBalanceException;
use Tests\TestCase;

class LeaveBalanceTest extends TestCase
{
    private function makeBalance(int $opening = 480, int $accrued = 480, int $used = 0): LeaveBalance
    {
        return new LeaveBalance(
            new LeaveBalanceId('00000000-0000-0000-0000-000000000001'),
            'emp-1',
            new LeaveTypeId('00000000-0000-0000-0000-000000000002'),
            2026,
            $opening, $accrued, $used, 0, 0,
        );
    }

    public function test_remaining_computation(): void
    {
        $b = $this->makeBalance(480, 480, 240);
        $this->assertEquals(720, $b->remaining());
    }

    public function test_deduct_reduces_remaining(): void
    {
        $b = $this->makeBalance(480, 0, 0);
        $event = $b->deduct(480);
        $this->assertEquals(480, $b->used());
        $this->assertEquals(0, $b->remaining());
        $this->assertInstanceOf(LeaveBalanceAdjusted::class, $event);
    }

    public function test_deduct_insufficient_throws(): void
    {
        $this->expectException(InsufficientBalanceException::class);
        $b = $this->makeBalance(240, 0, 0);
        $b->deduct(480);
    }

    public function test_restore(): void
    {
        $b = $this->makeBalance(480, 0, 480);
        $event = $b->restore(240);
        $this->assertEquals(240, $b->used());
        $this->assertInstanceOf(LeaveBalanceAdjusted::class, $event);
    }

    public function test_restore_below_zero(): void
    {
        $b = $this->makeBalance(0, 0, 480);
        $b->restore(500);
        $this->assertEquals(0, $b->used());
    }
}
