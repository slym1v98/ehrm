<?php
namespace App\Modules\Reporting\Application\Reports;
use App\Modules\Reporting\Application\Contracts\ReportQueryInterface;
use Illuminate\Support\Facades\DB;
class AttendanceSummaryReport implements ReportQueryInterface { public function execute(array $filters, string $requestedBy): array { return DB::table('attendance_timesheets')->select('employee_id')->selectRaw('COUNT(*) as work_days')->selectRaw("SUM(CASE WHEN result_status='present' THEN 1 ELSE 0 END) as present_days")->selectRaw("SUM(CASE WHEN result_status='absent' THEN 1 ELSE 0 END) as absent_days")->selectRaw('SUM(late_minutes) as late_minutes')->selectRaw('SUM(overtime_minutes) as overtime_minutes')->when($filters['period_id'] ?? null, fn($q,$v)=>$q->where('attendance_period_id',$v))->when($filters['employee_id'] ?? null, fn($q,$v)=>$q->where('employee_id',$v))->groupBy('employee_id')->get()->map(fn($r)=>(array)$r)->all(); } }
