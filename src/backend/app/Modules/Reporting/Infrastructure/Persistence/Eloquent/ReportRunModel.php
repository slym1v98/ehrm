<?php

namespace App\Modules\Reporting\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ReportRunModel extends Model
{
    protected $table = 'report_runs';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','report_definition_id','requested_by','filters','status','result','error','started_at','completed_at'];
    protected $casts = ['filters' => 'array', 'result' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
}
