<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriodModel extends Model
{
    protected $table = 'payroll_periods';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','period_code','start_date','end_date','cutoff_date','status','attendance_period_id','workflow_request_id','opened_by','opened_at','approved_by','approved_at','locked_by','locked_at','published_at'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','cutoff_date'=>'date','opened_at'=>'datetime','approved_at'=>'datetime','locked_at'=>'datetime','published_at'=>'datetime'];
}
