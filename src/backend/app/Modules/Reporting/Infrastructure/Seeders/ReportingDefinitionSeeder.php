<?php

namespace App\Modules\Reporting\Infrastructure\Seeders;

use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinition;
use App\Modules\Reporting\Domain\Aggregates\ReportDefinition\ReportDefinitionId;
use App\Modules\Reporting\Domain\Repositories\ReportDefinitionRepositoryInterface;
use Illuminate\Database\Seeder;

class ReportingDefinitionSeeder extends Seeder
{
    private array $reports = [
        ['code'=>'attendance.summary','name'=>'Attendance Summary','desc'=>'Attendance stats for a period','query'=>\App\Modules\Reporting\Application\Reports\AttendanceSummaryReport::class,'filters'=>[['key'=>'period_id','label'=>'Period ID','type'=>'string','required'=>true],['key'=>'employee_id','label'=>'Employee','type'=>'string','required'=>false]],'columns'=>[['key'=>'employee_id','label'=>'Employee ID','type'=>'string'],['key'=>'work_days','label'=>'Work Days','type'=>'integer']]],
        ['code'=>'leave.balance','name'=>'Leave Balance','desc'=>'Leave balance summary by year','query'=>\App\Modules\Reporting\Application\Reports\LeaveBalanceReport::class,'filters'=>[['key'=>'year','label'=>'Year','type'=>'integer','required'=>false],['key'=>'employee_id','label'=>'Employee','type'=>'string','required'=>false]],'columns'=>[['key'=>'employee_id','label'=>'Employee ID','type'=>'string'],['key'=>'leave_type','label'=>'Leave Type','type'=>'string'],['key'=>'remaining','label'=>'Remaining','type'=>'integer']]],
        ['code'=>'payroll.summary','name'=>'Payroll Summary','desc'=>'Payroll entry summary by period','query'=>\App\Modules\Reporting\Application\Reports\PayrollSummaryReport::class,'filters'=>[['key'=>'payroll_period_id','label'=>'Payroll Period ID','type'=>'string','required'=>true]],'columns'=>[['key'=>'employee_id','label'=>'Employee ID','type'=>'string'],['key'=>'net_pay','label'=>'Net Pay','type'=>'number']]],
        ['code'=>'workflow.pending','name'=>'Pending Workflows','desc'=>'Active pending workflow requests','query'=>\App\Modules\Reporting\Application\Reports\WorkflowPendingReport::class,'filters'=>[['key'=>'subject_type','label'=>'Subject Type','type'=>'string','required'=>false]],'columns'=>[['key'=>'request_id','label'=>'Request ID','type'=>'string'],['key'=>'status','label'=>'Status','type'=>'string']]],
        ['code'=>'notification.delivery','name'=>'Notification Delivery','desc'=>'Notification delivery stats by date range','query'=>\App\Modules\Reporting\Application\Reports\NotificationDeliveryReport::class,'filters'=>[['key'=>'from','label'=>'From','type'=>'date','required'=>true],['key'=>'to','label'=>'To','type'=>'date','required'=>true]],'columns'=>[['key'=>'channel','label'=>'Channel','type'=>'string'],['key'=>'total','label'=>'Total','type'=>'integer']]],
    ];

    public function run(ReportDefinitionRepositoryInterface $repo): void
    {
        foreach ($this->reports as $r) {
            $existing = $repo->findByCode($r['code']);
            if ($existing) continue;
            $def = ReportDefinition::create(ReportDefinitionId::generate(), $r['code'], $r['name'], $r['desc'], $r['query'], $r['filters'], $r['columns']);
            $repo->save($def);
        }
    }
}
