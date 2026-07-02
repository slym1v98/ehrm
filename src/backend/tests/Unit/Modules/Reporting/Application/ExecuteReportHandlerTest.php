<?php

namespace Tests\Unit\Modules\Reporting\Application;

use App\Modules\Reporting\Application\CommandHandlers\ExecuteReportHandler;
use App\Modules\Reporting\Application\Commands\ExecuteReportCommand;
use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinition;
use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinitionId;
use App\Modules\Reporting\Domain\Repositories\ReportDefinitionRepositoryInterface;
use App\Modules\Reporting\Domain\Repositories\ReportRunRepositoryInterface;
use App\Modules\Reporting\Domain\Exceptions\ReportDefinitionNotFoundException;
use PHPUnit\Framework\TestCase;

class ExecuteReportHandlerTest extends TestCase
{
    public function test_creates_report_run(): void
    {
        $definition = ReportDefinition::create(ReportDefinitionId::generate(), 'attendance.summary', 'Attendance', null, 'Query');
        $defs = $this->createMock(ReportDefinitionRepositoryInterface::class);
        $defs->method('findByCode')->willReturn($definition);
        $runs = $this->createMock(ReportRunRepositoryInterface::class);
        $runs->expects($this->once())->method('save');

        $handler = new ExecuteReportHandler($defs, $runs);
        $run = $handler->handle(new ExecuteReportCommand('attendance.summary', 'user-1', ['period_id' => 'p1']));

        $this->assertSame('user-1', $run->getRequestedBy());
        $this->assertSame((string) $definition->getId(), $run->getReportDefinitionId());
    }

    public function test_missing_definition_throws(): void
    {
        $defs = $this->createMock(ReportDefinitionRepositoryInterface::class);
        $defs->method('findByCode')->willReturn(null);
        $runs = $this->createMock(ReportRunRepositoryInterface::class);
        $handler = new ExecuteReportHandler($defs, $runs);

        $this->expectException(ReportDefinitionNotFoundException::class);
        $handler->handle(new ExecuteReportCommand('missing', 'user-1'));
    }
}
