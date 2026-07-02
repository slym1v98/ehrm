<?php
namespace App\Modules\Reporting\Application\Reports;
use App\Modules\Reporting\Application\Contracts\ReportQueryInterface;
use Illuminate\Support\Facades\DB;
class LeaveBalanceReport implements ReportQueryInterface { public function execute(array $filters, string $requestedBy): array { return DB::table('leave_balances')->select('employee_id','leave_type_id as leave_type','year','opening_minutes','accrued_minutes','used_minutes','carry_over_minutes','remaining_minutes')->when($filters['year'] ?? null, fn($q,$v)=>$q->where('year',$v))->when($filters['employee_id'] ?? null, fn($q,$v)=>$q->where('employee_id',$v))->get()->map(fn($r)=>(array)$r)->all(); } }
