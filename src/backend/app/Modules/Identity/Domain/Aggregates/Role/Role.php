<?php

namespace App\Modules\Identity\Domain\Aggregates\Role;

use App\Modules\Identity\Domain\Events\RoleCreated;
use App\Modules\Identity\Domain\Events\RolePermissionGranted;
use App\Modules\Identity\Domain\Events\RolePermissionRevoked;
use App\Modules\Identity\Domain\Events\RoleUpdated;
use App\Modules\Identity\Domain\Exceptions\PermissionAlreadyGrantedException;
use DateTimeImmutable;

final class Role
{
    /** @var object[] */
    private array $recordedEvents = [];

    /** @var RolePermission[] */
    private array $permissions = [];

    private function __construct(
        private readonly RoleId $id,
        private RoleCode $code,
        private RoleName $name,
        private ?string $description,
        private bool $active,
    ) {
    }

    public static function create(RoleId $id, RoleCode $code, RoleName $name, ?string $description = null): self
    {
        $role = new self($id, $code, $name, $description, true);
        $role->record(new RoleCreated($id, $code, new DateTimeImmutable()));
        return $role;
    }

    public static function reconstitute(RoleId $id, RoleCode $code, RoleName $name, ?string $description, bool $active, array $permissions): self
    {
        $role = new self($id, $code, $name, $description, $active);
        $role->permissions = $permissions;
        return $role;
    }

    public function id(): RoleId { return $this->id; }
    public function code(): RoleCode { return $this->code; }
    public function name(): RoleName { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function isActive(): bool { return $this->active; }
    public function permissions(): array { return $this->permissions; }

    public function update(RoleName $name, ?string $description): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->record(new RoleUpdated($this->id, new DateTimeImmutable()));
    }

    public function activate(): void
    {
        if ($this->active) return;
        $this->active = true;
        $this->record(new RoleUpdated($this->id, new DateTimeImmutable()));
    }

    public function deactivate(): void
    {
        if (! $this->active) return;
        $this->active = false;
        $this->record(new RoleUpdated($this->id, new DateTimeImmutable()));
    }

    public function grantPermission(PermissionCode $permissionCode): void
    {
        foreach ($this->permissions as $rp) {
            if ($rp->permissionCode->equals($permissionCode)) {
                throw new PermissionAlreadyGrantedException("{$permissionCode} already granted");
            }
        }
        $now = new DateTimeImmutable();
        $this->permissions[] = new RolePermission($permissionCode, $now);
        $this->record(new RolePermissionGranted($this->id, $permissionCode, $now));
    }

    public function revokePermission(PermissionCode $permissionCode): void
    {
        $now = new DateTimeImmutable();
        foreach ($this->permissions as $i => $rp) {
            if ($rp->permissionCode->equals($permissionCode)) {
                unset($this->permissions[$i]);
                $this->permissions = array_values($this->permissions);
                $this->record(new RolePermissionRevoked($this->id, $permissionCode, $now));
                return;
            }
        }
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
