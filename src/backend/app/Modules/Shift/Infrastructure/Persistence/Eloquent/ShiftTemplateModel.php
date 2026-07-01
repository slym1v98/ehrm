<?php

namespace App\Modules\Shift\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ShiftTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'shift_templates';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_overnight' => 'boolean',
            'active' => 'boolean',
            'overtime_rules' => 'array',
            'flexibility_rules' => 'array',
            'break_minutes' => 'integer',
            'late_tolerance_minutes' => 'integer',
        ];
    }
}
