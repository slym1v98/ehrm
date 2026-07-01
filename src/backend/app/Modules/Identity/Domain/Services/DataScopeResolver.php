<?php

namespace App\Modules\Identity\Domain\Services;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Database\Eloquent\Builder;

class DataScopeResolver
{
    public function apply(Builder $query, UserModel $user): Builder
    {
        $scopes = $user->dataScopeAssignments()->get();

        if ($scopes->contains('scope_type', 'all_company')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user, $scopes) {
            if ($scopes->contains('scope_type', 'self')) {
                $q->orWhere('id', $user->id);
            }

            foreach ($scopes->where('scope_type', 'branch') as $scope) {
                $q->orWhere('branch_id', $scope->branch_id);
            }

            foreach ($scopes->where('scope_type', 'department') as $scope) {
                $q->orWhere('department_id', $scope->department_id);
            }
        });
    }
}
