<?php

namespace App\Modules\Shift\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAssignmentModel extends Model
{
    use HasUuids;

    protected $table = 'shift_assignments';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'active' => 'boolean',
            'recurrence_rule' => 'array',
        ];
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplateModel::class, 'shift_template_id');
    }
}
