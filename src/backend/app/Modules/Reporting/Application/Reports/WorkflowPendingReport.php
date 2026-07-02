<?php
namespace App\Modules\Reporting\Application\Reports;
use App\Modules\Reporting\Application\Contracts\ReportQueryInterface;
use Illuminate\Support\Facades\DB;
class WorkflowPendingReport implements ReportQueryInterface { public function execute(array $filters, string $requestedBy): array { return DB::table('workflow_requests')->select('id as request_id','subject_type','subject_id','current_step_order as current_step','status','started_at')->where('status','pending')->when($filters['subject_type'] ?? null, fn($q,$v)=>$q->where('subject_type',$v))->get()->map(fn($r)=>(array)$r)->all(); } }
