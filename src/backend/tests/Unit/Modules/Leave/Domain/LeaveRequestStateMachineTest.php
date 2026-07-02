<?php
namespace Tests\Unit\Modules\Leave\Domain;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequest;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequestId;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use App\Modules\Leave\Domain\Events\LeaveRequestApproved;
use App\Modules\Leave\Domain\Events\LeaveRequestCancelled;
use App\Modules\Leave\Domain\Events\LeaveRequestRejected;
use App\Modules\Leave\Domain\Events\LeaveRequestSubmitted;
use App\Modules\Leave\Domain\Exceptions\InvalidLeaveStatusTransitionException;
use App\Modules\Leave\Domain\ValueObjects\DurationUnit;
use App\Modules\Leave\Domain\ValueObjects\LeavePeriod;
use App\Modules\Leave\Domain\ValueObjects\LeaveStatus;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class LeaveRequestStateMachineTest extends TestCase
{
    private function makeRequest(LeaveStatus $status = LeaveStatus::PENDING): LeaveRequest
    {
        return new LeaveRequest(
            new LeaveRequestId('00000000-0000-0000-0000-000000000001'),
            'emp-1',
            new LeaveTypeId('00000000-0000-0000-0000-000000000002'),
            new LeavePeriod(CarbonImmutable::parse('2026-07-15'), CarbonImmutable::parse('2026-07-15'), DurationUnit::DAY, 480),
            DurationUnit::DAY,
            'vacation',
            $status,
        );
    }

    public function test_submit_creates_pending(): void
    {
        $r = $this->makeRequest();
        $this->assertEquals(LeaveStatus::PENDING, $r->status());
        $event = $r->submittedEvent();
        $this->assertInstanceOf(LeaveRequestSubmitted::class, $event);
    }

    public function test_approve_pending(): void
    {
        $r = $this->makeRequest();
        $event = $r->approve('admin-1');
        $this->assertEquals(LeaveStatus::APPROVED, $r->status());
        $this->assertInstanceOf(LeaveRequestApproved::class, $event);
    }

    public function test_approve_already_approved_throws(): void
    {
        $this->expectException(InvalidLeaveStatusTransitionException::class);
        $r = $this->makeRequest(LeaveStatus::APPROVED);
        $r->approve('admin-1');
    }

    public function test_approve_rejected_throws(): void
    {
        $this->expectException(InvalidLeaveStatusTransitionException::class);
        $r = $this->makeRequest(LeaveStatus::REJECTED);
        $r->approve('admin-1');
    }

    public function test_reject_pending(): void
    {
        $r = $this->makeRequest();
        $event = $r->reject('admin-1', 'no reason');
        $this->assertEquals(LeaveStatus::REJECTED, $r->status());
        $this->assertInstanceOf(LeaveRequestRejected::class, $event);
    }

    public function test_reject_already_approved_throws(): void
    {
        $this->expectException(InvalidLeaveStatusTransitionException::class);
        $r = $this->makeRequest(LeaveStatus::APPROVED);
        $r->reject('admin-1', 'no');
    }

    public function test_cancel_pending(): void
    {
        $r = $this->makeRequest();
        $event = $r->cancel('emp-1');
        $this->assertEquals(LeaveStatus::CANCELLED, $r->status());
        $this->assertInstanceOf(LeaveRequestCancelled::class, $event);
    }

    public function test_cancel_approved(): void
    {
        $r = $this->makeRequest();
        $r->approve('admin-1');
        $event = $r->cancel('emp-1');
        $this->assertEquals(LeaveStatus::CANCELLED, $r->status());
        $this->assertInstanceOf(LeaveRequestCancelled::class, $event);
    }

    public function test_cancel_rejected_throws(): void
    {
        $this->expectException(InvalidLeaveStatusTransitionException::class);
        $r = $this->makeRequest(LeaveStatus::REJECTED);
        $r->cancel('emp-1');
    }

    public function test_cancel_cancelled_throws(): void
    {
        $this->expectException(InvalidLeaveStatusTransitionException::class);
        $r = $this->makeRequest(LeaveStatus::CANCELLED);
        $r->cancel('emp-1');
    }
}
