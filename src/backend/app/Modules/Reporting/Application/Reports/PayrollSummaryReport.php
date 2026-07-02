<?php
namespace App\Modules\Reporting\Application\Reports;
use App\Modules\Reporting\Application\Contracts\ReportQueryInterface;
use Illuminate\Support\Facades\DB;
class PayrollSummaryReport implements ReportQueryInterface { public function execute(array $filters, string $requestedBy): array { return DB::table('payroll_entries')->select('employee_id','status')->selectRaw('gross_pay, total_deductions as deductions, net_pay')->when($filters['payroll_period_id'] ?? null, fn($q,$v)=>$q->where('payroll_period_id',$v))->when($filters['employee_id'] ?? null, fn($q,$v)=>$q->where('employee_id',$v))->get()->map(fn($r)=>(array)$r)->all(); } }
