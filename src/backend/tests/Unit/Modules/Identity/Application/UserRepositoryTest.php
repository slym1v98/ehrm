<?php

namespace Tests\Unit\Modules\Identity\Application;

use App\Modules\Identity\Domain\Aggregates\Role\PermissionCode;
use App\Modules\Identity\Domain\Aggregates\Role\Role;
use App\Modules\Identity\Domain\Aggregates\Role\RoleCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\Role\RoleName;
use App\Modules\Identity\Domain\Aggregates\User\DataScope;
use App\Modules\Identity\Domain\Aggregates\User\Email;
use App\Modules\Identity\Domain\Aggregates\User\HashedPassword;
use App\Modules\Identity\Domain\Aggregates\User\ScopeType;
use App\Modules\Identity\Domain\Aggregates\User\User;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use App\Modules\Identity\Domain\Aggregates\User\UserName;
use App\Modules\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\PermissionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_repository_saves_and_finds_user(): void
    {
        $repo = app(UserRepositoryInterface::class);
        $user = User::create(
            UserId::generate(),
            Email::fromString('repo@ihrm.local'),
            HashedPassword::fromHash('$2y$hash'),
            UserName::fromString('Repo User'),
        );
        $userId = $user->id();
        $user->releaseEvents();

        $repo->save($user);

        $found = $repo->findById($userId);
        $this->assertNotNull($found);
        $this->assertSame('repo@ihrm.local', (string) $found->email());
        $this->assertTrue($repo->existsByEmail(Email::fromString('repo@ihrm.local')));
    }

    public function test_role_repository_saves_permissions(): void
    {
        PermissionModel::create([
            'code' => 'identity.user.list',
            'module' => 'identity',
            'action' => 'user.list',
            'description' => 'List users',
            'active' => true,
        ]);

        $repo = app(RoleRepositoryInterface::class);
        $role = Role::create(
            RoleId::generate(),
            RoleCode::fromString('HR_MANAGER'),
            RoleName::fromString('HR Manager'),
            'HR management',
        );
        $role->grantPermission(PermissionCode::fromString('identity.user.list'));
        $roleId = $role->id();
        $role->releaseEvents();

        $repo->save($role);

        $found = $repo->findById($roleId);
        $this->assertNotNull($found);
        $this->assertCount(1, $found->permissions());
    }

    public function test_user_repository_saves_role_and_scope_assignments(): void
    {
        $roleRepo = app(RoleRepositoryInterface::class);
        $userRepo = app(UserRepositoryInterface::class);

        $role = Role::create(RoleId::generate(), RoleCode::fromString('SUPER_ADMIN'), RoleName::fromString('Super Admin'));
        $roleId = $role->id();
        $role->releaseEvents();
        $roleRepo->save($role);

        $user = User::create(
            UserId::generate(),
            Email::fromString('scoped@ihrm.local'),
            HashedPassword::fromHash('$2y$hash'),
            UserName::fromString('Scoped User'),
        );
        $user->assignRole($roleId, assignedBy: null);
        $user->grantDataScope(new DataScope(ScopeType::AllCompany));
        $userId = $user->id();
        $user->releaseEvents();
        $userRepo->save($user);

        $found = $userRepo->findById($userId);
        $this->assertNotNull($found);
        $this->assertCount(1, $found->roleBindings());
        $this->assertCount(1, $found->dataScopeAssignments());
    }
}
