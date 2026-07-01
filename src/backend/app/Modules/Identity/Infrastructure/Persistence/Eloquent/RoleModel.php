<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleModel extends Model
{
    use HasUuids;

    protected $table = 'roles';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['active' => 'boolean'];

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermissionModel::class, 'role_id');
    }
}
