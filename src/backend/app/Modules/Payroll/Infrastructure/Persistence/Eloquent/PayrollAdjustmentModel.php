<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PayrollAdjustmentModel extends Model
{
    protected $table = 'payroll_adjustments';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','entry_id','component_id','adjustment_type','amount','reason','status','submitted_by','submitted_at','approved_by','approved_at','rejected_reason'];
    protected $casts = ['amount'=>'decimal:2','submitted_at'=>'datetime','approved_at'=>'datetime'];
}
