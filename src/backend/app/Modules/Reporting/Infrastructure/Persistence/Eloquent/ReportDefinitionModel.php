<?php

namespace App\Modules\Reporting\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ReportDefinitionModel extends Model
{
    protected $table = 'report_definitions';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','code','name','description','query_class','filters_schema','columns_schema','is_active'];
    protected $casts = ['filters_schema' => 'array', 'columns_schema' => 'array', 'is_active' => 'boolean'];
}
