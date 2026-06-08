<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Policy/RbacPolicy.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Policy;

use PharmaControl\Auth\Domain\Exception\HierarchyViolationException;
use PharmaControl\Auth\Domain\Exception\MaxRolesExceededException;
use PharmaControl\Auth\Domain\Exception\SoDViolationException;
use PharmaControl\Auth\Domain\Exception\SuperAdminIsExclusiveException;
use PharmaControl\Auth\Domain\Model\Role;
use PharmaControl\Auth\Domain\ValueObject\RoleId;

final class RbacPolicy
{
    public const MAX_ROLES_PER_USER = 3;

    public const MAX_BRANCHES = 5;

    public function assertHierarchy(Role $target, Role $actor): void
    {
        if ($target->hierarchyLevel > $actor->hierarchyLevel) {
            throw new HierarchyViolationException($target->hierarchyLevel, $actor->hierarchyLevel);
        }
    }

    /**
     * @param  list<RoleId>  $currentRoleIds
     * @param  list<RoleId>  $exclusions  IDs of roles that conflict with the new role
     */
    public function assertNoSoDConflict(array $currentRoleIds, array $exclusions, string $newRoleName, array $currentRoleNames = []): void
    {
        $currentValues = array_map(fn (RoleId $r) => $r->value, $currentRoleIds);
        foreach ($exclusions as $excluded) {
            if (in_array($excluded->value, $currentValues, true)) {
                $conflictName = $currentRoleNames[$excluded->value] ?? $excluded->value;
                throw new SoDViolationException($newRoleName, $conflictName);
            }
        }
    }

    public function assertMaxRoles(int $currentCount, int $max = self::MAX_ROLES_PER_USER): void
    {
        if ($currentCount >= $max) {
            throw new MaxRolesExceededException($max);
        }
    }

    /** @param list<Role> $currentRoles */
    public function assertSuperAdminExclusive(array $currentRoles, Role $newRole): void
    {
        if ($newRole->isSuperAdmin() && count($currentRoles) > 0) {
            throw new SuperAdminIsExclusiveException;
        }

        foreach ($currentRoles as $existing) {
            if ($existing->isSuperAdmin()) {
                throw new SuperAdminIsExclusiveException;
            }
        }
    }
}
