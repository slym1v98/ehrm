<?php

namespace Tests\Unit\Modules\Payroll\Domain;

use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\{PayrollPeriod, PayrollPeriodId};
use App\Modules\Payroll\Domain\Exceptions\PayrollNotApprovedException;
use App\Modules\Payroll\Domain\Exceptions\PayrollPeriodLockedException;
use App\Modules\Payroll\Domain\ValueObjects\PeriodStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PayrollPeriodTest extends TestCase
{
    private function newPeriod(): PayrollPeriod
    {
        return PayrollPeriod::open(
            PayrollPeriodId::generate(),
            '2026-06',
            new DateTimeImmutable('2026-06-01'),
            new DateTimeImmutable('2026-06-30'),
            new DateTimeImmutable('2026-06-25'),
            null,
            'user-1',
        );
    }

    public function test_open_creates_period_in_open_status(): void
    {
        $period = $this->newPeriod();
        $this->assertSame(PeriodStatus::Open, $period->getStatus());
        $this->assertCount(1, $period->getRecordedEvents());
    }

    public function test_full_lifecycle(): void
    {
        $period = $this->newPeriod();
        $period->startRun('user-1');
        $this->assertSame(PeriodStatus::Calculating, $period->getStatus());

        $period->completeRun();
        $this->assertSame(PeriodStatus::Completed, $period->getStatus());

        $period->submitForApproval('wfl-1');
        $this->assertSame(PeriodStatus::Reviewing, $period->getStatus());

        $period->approve('mgr-1');
        $this->assertSame(PeriodStatus::Approved, $period->getStatus());

        $period->lock('officer-1');
        $this->assertSame(PeriodStatus::Locked, $period->getStatus());

        $period->publish('officer-1');
        $this->assertSame(PeriodStatus::Published, $period->getStatus());
    }

    public function test_lock_without_approval_fails(): void
    {
        $period = $this->newPeriod();
        $period->startRun('u1');
        $period->completeRun();
        $this->expectException(PayrollNotApprovedException::class);
        $period->lock('officer-1');
    }

    public function test_locked_period_cannot_start_run(): void
    {
        $period = $this->newPeriod();
        $period->startRun('u1'); $period->completeRun();
        $period->submitForApproval('wfl-1'); $period->approve('m1'); $period->lock('l1');

        $this->expectException(\RuntimeException::class);
        $period->startRun('u2');
    }

    public function test_published_period_cannot_reopen(): void
    {
        $period = $this->newPeriod();
        $period->startRun('u1'); $period->completeRun();
        $period->submitForApproval('wfl-1'); $period->approve('m1'); $period->lock('l1');
        $period->publish('p1');

        $this->expectException(PayrollPeriodLockedException::class);
        $period->reopen('admin');
    }

    public function test_reject_returns_to_completed(): void
    {
        $period = $this->newPeriod();
        $period->startRun('u1'); $period->completeRun();
        $period->submitForApproval('wfl-1');
        $period->reject();
        $this->assertSame(PeriodStatus::Completed, $period->getStatus());
    }
}
