<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Model/Role.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\PermissionName;
use PharmaControl\Auth\Domain\ValueObject\RoleId;

final class Role
{
    /** @param list<PermissionName> $permissions */
    private function __construct(
        public readonly RoleId $id,
        public readonly string $name,
        public readonly string $displayName,
        public readonly int $hierarchyLevel,
        public readonly bool $branchScoped,
        private array $permissions,
    ) {}

    /** @param list<PermissionName> $permissions */
    public static function create(
        RoleId $id,
        string $name,
        string $displayName,
        int $hierarchyLevel,
        bool $branchScoped,
        array $permissions = [],
    ): self {
        if ($hierarchyLevel < 1 || $hierarchyLevel > 10) {
            throw new \InvalidArgumentException("Nivel de jerarquía fuera de rango [1-10]: {$hierarchyLevel}");
        }

        return new self($id, $name, $displayName, $hierarchyLevel, $branchScoped, $permissions);
    }

    /** @param list<PermissionName> $permissions */
    public static function reconstitute(
        RoleId $id,
        string $name,
        string $displayName,
        int $hierarchyLevel,
        bool $branchScoped,
        array $permissions,
    ): self {
        return new self($id, $name, $displayName, $hierarchyLevel, $branchScoped, $permissions);
    }

    /** @return list<PermissionName> */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasPermission(PermissionName $permission): bool
    {
        foreach ($this->permissions as $p) {
            if ($p->equals($permission)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === 'super-admin';
    }
}
