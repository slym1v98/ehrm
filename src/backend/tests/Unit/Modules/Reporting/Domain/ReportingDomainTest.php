<?php

namespace Tests\Unit\Modules\Reporting\Domain;

use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinition;
use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinitionId;
use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRun;
use App\Modules\Reporting\Domain\Aggregates\ReportRun\ReportRunId;
use App\Modules\Reporting\Domain\ValueObjects\ReportRunStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ReportingDomainTest extends TestCase
{
    public function test_report_definition_activate_deactivate(): void
    {
        $definition = ReportDefinition::create(ReportDefinitionId::generate(), 'attendance.summary', 'Attendance', null, 'Query');
        $this->assertTrue($definition->isActive());
        $definition->deactivate();
        $this->assertFalse($definition->isActive());
        $definition->activate();
        $this->assertTrue($definition->isActive());
    }

    public function test_report_run_transitions(): void
    {
        $run = ReportRun::request(ReportRunId::generate(), 'def-1', 'user-1', []);
        $this->assertSame(ReportRunStatus::Requested, $run->getStatus());
        $run->start(CarbonImmutable::now());
        $this->assertSame(ReportRunStatus::Running, $run->getStatus());
        $run->complete([['x' => 1]], CarbonImmutable::now());
        $this->assertSame(ReportRunStatus::Completed, $run->getStatus());
    }

    public function test_invalid_transition_rejected(): void
    {
        $run = ReportRun::request(ReportRunId::generate(), 'def-1', 'user-1', []);
        $this->expectException(\InvalidArgumentException::class);
        $run->complete([], CarbonImmutable::now());
    }
}
