<?php

namespace App\Modules\Workflow\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class WorkflowRequestActionModel extends Model
{
    public $timestamps = false;
    protected $table = 'workflow_request_actions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'workflow_request_id', 'step_order', 'action', 'actor_id', 'comment', 'metadata', 'created_at'];

    protected $casts = [
        'step_order' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
}
