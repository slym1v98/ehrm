<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PayrollEntryModel extends Model
{
    protected $table = 'payroll_entries';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','run_id','period_id','employee_id','contract_snapshot','attendance_snapshot','leave_snapshot','gross_amount','deduction_amount','net_amount','status','error_message','reviewed_by','reviewed_at'];
    protected $casts = ['contract_snapshot'=>'array','attendance_snapshot'=>'array','leave_snapshot'=>'array','gross_amount'=>'decimal:2','deduction_amount'=>'decimal:2','net_amount'=>'decimal:2','reviewed_at'=>'datetime'];
    public function lines(){ return $this->hasMany(PayrollEntryLineModel::class, 'entry_id'); }
}
