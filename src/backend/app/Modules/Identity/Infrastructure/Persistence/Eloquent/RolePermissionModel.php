<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermissionModel extends Model
{
    use HasUuids;

    protected $table = 'role_permissions';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(PermissionModel::class, 'permission_code', 'code');
    }
}
