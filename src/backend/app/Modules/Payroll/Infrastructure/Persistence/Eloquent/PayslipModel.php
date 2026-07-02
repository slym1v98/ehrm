<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PayslipModel extends Model
{
    protected $table = 'payslips';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','entry_id','employee_id','period_id','gross','deductions','net','payload','status','published_at','first_accessed_at','access_count'];
    protected $casts = ['gross'=>'decimal:2','deductions'=>'decimal:2','net'=>'decimal:2','payload'=>'array','published_at'=>'datetime','first_accessed_at'=>'datetime'];
}
