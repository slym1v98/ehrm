<?php

namespace Tests\Unit\Modules\Identity\Domain;

use App\Modules\Identity\Domain\Services\DataScopeResolver;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\DataScopeAssignmentModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataScopeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_company_scope_does_not_filter_query(): void
    {
        $user = UserModel::create([
            'name' => 'Scoped',
            'email' => 'scope@ihrm.local',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        DataScopeAssignmentModel::create(['user_id' => $user->id, 'scope_type' => 'all_company']);

        $sql = app(DataScopeResolver::class)->apply(UserModel::query(), $user)->toSql();

        $this->assertSame('select * from "users"', $sql);
    }
}
