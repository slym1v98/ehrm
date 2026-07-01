<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Events\UserCreated;
use App\Modules\Identity\Domain\Events\UserDataScopeGranted;
use App\Modules\Identity\Domain\Events\UserDisabled;
use App\Modules\Identity\Domain\Events\UserLoggedIn;
use App\Modules\Identity\Domain\Events\UserPasswordChanged;
use App\Modules\Identity\Domain\Events\UserReactivated;
use App\Modules\Identity\Domain\Events\UserRoleAssigned;
use App\Modules\Identity\Domain\Events\UserRoleRevoked;
use App\Modules\Identity\Domain\Exceptions\RoleAlreadyAssignedException;
use DateTimeImmutable;

final class User
{
    /** @var object[] */
    private array $recordedEvents = [];

    /** @var RoleBinding[] */
    private array $roleBindings = [];

    /** @var DataScopeAssignment[] */
    private array $dataScopeAssignments = [];

    private ?DateTimeImmutable $lastLoginAt = null;

    private function __construct(
        private readonly UserId $id,
        private ?EmployeeId $employeeId,
        private Email $email,
        private HashedPassword $passwordHash,
        private UserName $name,
        private UserStatus $status,
    ) {
    }

    public static function create(UserId $id, Email $email, HashedPassword $passwordHash, UserName $name, ?EmployeeId $employeeId = null): self
    {
        $user = new self($id, $employeeId, $email, $passwordHash, $name, UserStatus::Active);
        $user->record(new UserCreated($id, $email, new DateTimeImmutable()));
        return $user;
    }

    public static function reconstitute(
        UserId $id,
        ?EmployeeId $employeeId,
        Email $email,
        HashedPassword $passwordHash,
        UserName $name,
        UserStatus $status,
        ?DateTimeImmutable $lastLoginAt,
        array $roleBindings,
        array $dataScopeAssignments,
    ): self {
        $user = new self($id, $employeeId, $email, $passwordHash, $name, $status);
        $user->lastLoginAt = $lastLoginAt;
        $user->roleBindings = $roleBindings;
        $user->dataScopeAssignments = $dataScopeAssignments;
        return $user;
    }

    public function id(): UserId { return $this->id; }
    public function employeeId(): ?EmployeeId { return $this->employeeId; }
    public function email(): Email { return $this->email; }
    public function passwordHash(): HashedPassword { return $this->passwordHash; }
    public function name(): UserName { return $this->name; }
    public function status(): UserStatus { return $this->status; }
    public function lastLoginAt(): ?DateTimeImmutable { return $this->lastLoginAt; }
    public function roleBindings(): array { return $this->roleBindings; }
    public function dataScopeAssignments(): array { return $this->dataScopeAssignments; }

    public function isActive(): bool { return $this->status === UserStatus::Active; }

    public function disable(): void
    {
        if ($this->status === UserStatus::Disabled) return;
        $this->status = UserStatus::Disabled;
        $this->record(new UserDisabled($this->id, new DateTimeImmutable()));
    }

    public function reactivate(): void
    {
        if ($this->status === UserStatus::Active) return;
        $this->status = UserStatus::Active;
        $this->record(new UserReactivated($this->id, new DateTimeImmutable()));
    }

    public function changePassword(HashedPassword $new): void
    {
        $this->passwordHash = $new;
        $this->record(new UserPasswordChanged($this->id, new DateTimeImmutable()));
    }

    public function recordLogin(): void
    {
        $this->lastLoginAt = new DateTimeImmutable();
        $this->record(new UserLoggedIn($this->id, $this->lastLoginAt));
    }

    public function assignRole(RoleId $roleId, ?UserId $assignedBy): void
    {
        if ($this->hasActiveRole($roleId)) {
            throw new RoleAlreadyAssignedException("Role {$roleId} already assigned");
        }
        $now = new DateTimeImmutable();
        $this->roleBindings[] = new RoleBinding($roleId, $assignedBy, $now);
        $this->record(new UserRoleAssigned($this->id, $roleId, $assignedBy, $now));
    }

    public function revokeRole(RoleId $roleId): void
    {
        $now = new DateTimeImmutable();
        foreach ($this->roleBindings as $binding) {
            if ($binding->roleId->equals($roleId) && $binding->isActive()) {
                $binding->revoke($now);
                $this->record(new UserRoleRevoked($this->id, $roleId, $now));
                return;
            }
        }
    }

    public function hasActiveRole(RoleId $roleId): bool
    {
        foreach ($this->roleBindings as $binding) {
            if ($binding->roleId->equals($roleId) && $binding->isActive()) {
                return true;
            }
        }
        return false;
    }

    public function grantDataScope(DataScope $scope): void
    {
        $now = new DateTimeImmutable();
        $assignment = new DataScopeAssignment(
            id: \Ramsey\Uuid\Uuid::uuid4()->toString(),
            scope: $scope,
            createdAt: $now,
        );
        $this->dataScopeAssignments[] = $assignment;
        $this->record(new UserDataScopeGranted($this->id, $scope, $now));
    }

    private function record(object $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /** @return object[] */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }
}
