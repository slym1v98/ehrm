<?php

namespace Tests\Unit\Modules\Identity\Domain;

use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\User\DataScope;
use App\Modules\Identity\Domain\Aggregates\User\Email;
use App\Modules\Identity\Domain\Aggregates\User\HashedPassword;
use App\Modules\Identity\Domain\Aggregates\User\ScopeType;
use App\Modules\Identity\Domain\Aggregates\User\User;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use App\Modules\Identity\Domain\Aggregates\User\UserName;
use App\Modules\Identity\Domain\Aggregates\User\UserStatus;
use App\Modules\Identity\Domain\Events\UserCreated;
use App\Modules\Identity\Domain\Events\UserDisabled;
use App\Modules\Identity\Domain\Events\UserPasswordChanged;
use App\Modules\Identity\Domain\Events\UserReactivated;
use App\Modules\Identity\Domain\Events\UserRoleAssigned;
use App\Modules\Identity\Domain\Events\UserRoleRevoked;
use App\Modules\Identity\Domain\Exceptions\RoleAlreadyAssignedException;
use PHPUnit\Framework\TestCase;

class UserAggregateTest extends TestCase
{
    private function makeUser(): User
    {
        return User::create(
            UserId::generate(),
            Email::fromString('u@x.com'),
            HashedPassword::fromHash('$2y$hash'),
            UserName::fromString('Alice'),
        );
    }

    public function test_create_records_user_created_event(): void
    {
        $user = $this->makeUser();
        $events = $user->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreated::class, $events[0]);
        $this->assertSame(UserStatus::Active, $user->status());
    }

    public function test_disable_and_reactivate(): void
    {
        $user = $this->makeUser();
        $user->releaseEvents();
        $user->disable();
        $this->assertSame(UserStatus::Disabled, $user->status());
        $events = $user->releaseEvents();
        $this->assertInstanceOf(UserDisabled::class, $events[0]);

        $user->reactivate();
        $this->assertSame(UserStatus::Active, $user->status());
        $events = $user->releaseEvents();
        $this->assertInstanceOf(UserReactivated::class, $events[0]);
    }

    public function test_assign_role_emits_event_and_prevents_duplicates(): void
    {
        $user = $this->makeUser();
        $user->releaseEvents();
        $roleId = RoleId::generate();
        $user->assignRole($roleId, assignedBy: null);
        $events = $user->releaseEvents();
        $this->assertInstanceOf(UserRoleAssigned::class, $events[0]);

        $this->expectException(RoleAlreadyAssignedException::class);
        $user->assignRole($roleId, assignedBy: null);
    }

    public function test_revoke_role_emits_event(): void
    {
        $user = $this->makeUser();
        $roleId = RoleId::generate();
        $user->assignRole($roleId, assignedBy: null);
        $user->releaseEvents();

        $user->revokeRole($roleId);
        $events = $user->releaseEvents();
        $this->assertInstanceOf(UserRoleRevoked::class, $events[0]);
        $this->assertFalse($user->hasActiveRole($roleId));
    }

    public function test_change_password_emits_event(): void
    {
        $user = $this->makeUser();
        $user->releaseEvents();
        $user->changePassword(HashedPassword::fromHash('$2y$new'));
        $events = $user->releaseEvents();
        $this->assertInstanceOf(UserPasswordChanged::class, $events[0]);
    }

    public function test_grant_data_scope(): void
    {
        $user = $this->makeUser();
        $user->releaseEvents();
        $user->grantDataScope(new DataScope(ScopeType::AllCompany));
        $events = $user->releaseEvents();
        $this->assertNotEmpty($events);
    }
}
