<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DataScopeAssignmentModel extends Model
{
    use HasUuids;

    protected $table = 'data_scope_assignments';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
