<?php
namespace App\Modules\Reporting\Application\Reports;
use App\Modules\Reporting\Application\Contracts\ReportQueryInterface;
use Illuminate\Support\Facades\DB;
class NotificationDeliveryReport implements ReportQueryInterface { public function execute(array $filters, string $requestedBy): array { return DB::table('notification_messages')->select('channel')->selectRaw('COUNT(*) as total')->selectRaw("SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) as sent")->selectRaw("SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed")->selectRaw("SUM(CASE WHEN status IN ('pending','queued') THEN 1 ELSE 0 END) as pending")->when($filters['from'] ?? null, fn($q,$v)=>$q->where('created_at','>=',$v))->when($filters['to'] ?? null, fn($q,$v)=>$q->where('created_at','<=',$v))->when($filters['channel'] ?? null, fn($q,$v)=>$q->where('channel',$v))->when($filters['status'] ?? null, fn($q,$v)=>$q->where('status',$v))->groupBy('channel')->get()->map(fn($r)=>(array)$r)->all(); } }
