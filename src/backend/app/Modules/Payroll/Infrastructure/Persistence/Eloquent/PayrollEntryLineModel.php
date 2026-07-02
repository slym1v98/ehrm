<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PayrollEntryLineModel extends Model
{
    protected $table = 'payroll_entry_lines';
    protected $fillable = ['entry_id','component_id','category','amount','calculation_note'];
    protected $casts = ['amount'=>'decimal:2'];
}
