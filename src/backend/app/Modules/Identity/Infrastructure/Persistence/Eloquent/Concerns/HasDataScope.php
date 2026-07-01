<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Concerns;

use App\Modules\Identity\Domain\Services\DataScopeResolver;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Database\Eloquent\Builder;

trait HasDataScope
{
    public function scopeVisibleTo(Builder $query, UserModel $user): Builder
    {
        return app(DataScopeResolver::class)->apply($query, $user);
    }
}
