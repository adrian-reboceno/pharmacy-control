<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/AssignRole/AssignRoleUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\AssignRole;

use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Event\RoleAssigned;
use PharmaControl\Auth\Domain\Exception\BranchRequiredException;
use PharmaControl\Auth\Domain\Exception\DuplicateRoleAssignmentException;
use PharmaControl\Auth\Domain\Policy\RbacPolicy;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\ValueObject\BranchId;

final class AssignRoleUseCase
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly RoleRepositoryContract $roles,
        private readonly EventPublisherContract $events,
        private readonly RbacPolicy $rbacPolicy,
    ) {}

    public function execute(AssignRoleCommand $command): void
    {
        $targetUserId = new UserId($command->targetUserId);
        $actorUserId = new UserId($command->actorUserId);
        $actorRoleId = new RoleId($command->actorRoleId);
        $newRoleId = new RoleId($command->roleId);
        $branchId = $command->branchId ? new BranchId($command->branchId) : null;

        $actorRole = $this->roles->findById($actorRoleId);
        $newRole = $this->roles->findById($newRoleId);
        $currentRoles = $this->roles->getCurrentRoles($targetUserId);

        $this->rbacPolicy->assertHierarchy($newRole, $actorRole);

        $exclusions = $this->roles->getExclusions($newRoleId);
        $currentRoleIds = array_map(fn ($r) => $r->id, $currentRoles);
        $currentNames = array_combine(
            array_map(fn ($r) => $r->id->value, $currentRoles),
            array_map(fn ($r) => $r->name, $currentRoles)
        );
        $this->rbacPolicy->assertNoSoDConflict($currentRoleIds, $exclusions, $newRole->name, $currentNames);

        $this->rbacPolicy->assertMaxRoles(count($currentRoles));
        $this->rbacPolicy->assertSuperAdminExclusive($currentRoles, $newRole);

        if ($newRole->branchScoped && $branchId === null) {
            throw new BranchRequiredException($newRole->name);
        }

        foreach ($currentRoles as $existing) {
            if ($existing->id->equals($newRoleId)) {
                $isSameBranch = ($branchId === null && true) ||
                    ($branchId !== null);
                throw new DuplicateRoleAssignmentException($newRole->name, $branchId?->value);
            }
        }

        $this->roles->assignRole($targetUserId, $newRoleId, $branchId, $actorUserId);

        $this->events->publish(new RoleAssigned(
            $targetUserId,
            $newRoleId,
            $branchId,
            $actorUserId,
            new \DateTimeImmutable,
        ));
    }
}
