<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Repository/RoleRepositoryContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Repository;

use PharmaControl\Auth\Domain\Model\Role;
use PharmaControl\Auth\Domain\Model\RoleMetadata;
use PharmaControl\Auth\Domain\ValueObject\PermissionName;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\ValueObject\BranchId;

interface RoleRepositoryContract
{
    public function findById(RoleId $id): ?Role;

    public function findByName(string $name): ?Role;

    /** @return list<Role> */
    public function getCurrentRoles(UserId $userId): array;

    /** @return list<RoleId> */
    public function getExclusions(RoleId $roleId): array;

    public function getMetadata(RoleId $roleId): RoleMetadata;

    public function assignRole(UserId $userId, RoleId $roleId, ?BranchId $branchId, UserId $assignedBy): void;

    public function revokeRole(UserId $userId, RoleId $roleId): void;

    /** @return list<PermissionName> */
    public function resolvePermissions(RoleId $roleId): array;
}
