<?php

namespace App\Modules\Workflow\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class WorkflowTemplateModel extends Model
{
    protected $table = 'workflow_templates';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'name', 'description', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function steps()
    {
        return $this->hasMany(WorkflowTemplateStepModel::class, 'workflow_template_id')->orderBy('step_order');
    }
}
