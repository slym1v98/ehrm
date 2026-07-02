<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PayrollComponentModel extends Model
{
    protected $table = 'payroll_components';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','code','name','category','calculation_type','percent_base_component_id','default_amount','default_percent','taxable','active'];
    protected $casts = ['default_amount'=>'decimal:2','default_percent'=>'decimal:2','taxable'=>'bool','active'=>'bool'];
}
