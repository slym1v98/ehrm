<?php

namespace Tests\Unit\Modules\Identity\Domain;

use App\Modules\Identity\Domain\Aggregates\Role\PermissionCode;
use App\Modules\Identity\Domain\Aggregates\Role\Role;
use App\Modules\Identity\Domain\Aggregates\Role\RoleCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\Role\RoleName;
use App\Modules\Identity\Domain\Events\RoleCreated;
use App\Modules\Identity\Domain\Events\RolePermissionGranted;
use App\Modules\Identity\Domain\Events\RolePermissionRevoked;
use App\Modules\Identity\Domain\Exceptions\PermissionAlreadyGrantedException;
use PHPUnit\Framework\TestCase;

class RoleAggregateTest extends TestCase
{
    private function makeRole(): Role
    {
        return Role::create(
            RoleId::generate(),
            RoleCode::fromString('HR_MANAGER'),
            RoleName::fromString('HR Manager'),
            'HR management',
        );
    }

    public function test_create_records_event(): void
    {
        $role = $this->makeRole();
        $events = $role->releaseEvents();
        $this->assertInstanceOf(RoleCreated::class, $events[0]);
        $this->assertTrue($role->isActive());
    }

    public function test_grant_permission_and_prevent_duplicate(): void
    {
        $role = $this->makeRole();
        $role->releaseEvents();
        $permission = PermissionCode::fromString('identity.user.list');
        $role->grantPermission($permission);
        $events = $role->releaseEvents();
        $this->assertInstanceOf(RolePermissionGranted::class, $events[0]);

        $this->expectException(PermissionAlreadyGrantedException::class);
        $role->grantPermission($permission);
    }

    public function test_revoke_permission_emits_event(): void
    {
        $role = $this->makeRole();
        $permission = PermissionCode::fromString('identity.user.list');
        $role->grantPermission($permission);
        $role->releaseEvents();
        $role->revokePermission($permission);
        $events = $role->releaseEvents();
        $this->assertInstanceOf(RolePermissionRevoked::class, $events[0]);
    }

    public function test_deactivate(): void
    {
        $role = $this->makeRole();
        $role->releaseEvents();
        $role->deactivate();
        $this->assertFalse($role->isActive());
    }
}
