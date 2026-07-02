<?php

namespace App\Modules\Workflow\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class WorkflowRequestModel extends Model
{
    protected $table = 'workflow_requests';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'workflow_template_id', 'subject_type', 'subject_id', 'status', 'current_step', 'submitted_by'];

    protected $casts = ['current_step' => 'integer'];

    public function actions()
    {
        return $this->hasMany(WorkflowRequestActionModel::class, 'workflow_request_id')->orderBy('created_at');
    }
}
