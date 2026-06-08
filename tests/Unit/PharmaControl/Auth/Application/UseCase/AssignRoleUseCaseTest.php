<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Application/UseCase/AssignRoleUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Application\UseCase;

use PharmaControl\Auth\Application\UseCase\AssignRole\AssignRoleCommand;
use PharmaControl\Auth\Application\UseCase\AssignRole\AssignRoleUseCase;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Exception\BranchRequiredException;
use PharmaControl\Auth\Domain\Exception\HierarchyViolationException;
use PharmaControl\Auth\Domain\Exception\MaxRolesExceededException;
use PharmaControl\Auth\Domain\Exception\SuperAdminIsExclusiveException;
use PharmaControl\Auth\Domain\Model\Role;
use PharmaControl\Auth\Domain\Policy\RbacPolicy;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AssignRoleUseCaseTest extends TestCase
{
    private UserRepositoryContract&MockObject $users;

    private RoleRepositoryContract&MockObject $roles;

    private EventPublisherContract&MockObject $events;

    private AssignRoleUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = $this->createMock(UserRepositoryContract::class);
        $this->roles = $this->createMock(RoleRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);

        $this->useCase = new AssignRoleUseCase(
            $this->users,
            $this->roles,
            $this->events,
            new RbacPolicy,
        );
    }

    private function makeRole(string $name, int $level, bool $scoped = false): Role
    {
        return Role::create(RoleId::generate(), $name, ucfirst($name), $level, $scoped);
    }

    private function command(?string $branchId = null, ?string $newRoleId = null, ?string $actorRoleId = null): AssignRoleCommand
    {
        $newRole = $newRoleId ?? RoleId::generate()->value;
        $actorRole = $actorRoleId ?? RoleId::generate()->value;

        return new AssignRoleCommand(
            targetUserId: UserId::generate()->value,
            roleId: $newRole,
            branchId: $branchId,
            actorUserId: UserId::generate()->value,
            actorRoleId: $actorRole,
        );
    }

    public function test_assigns_role_successfully_when_all_validations_pass(): void
    {
        $actorRole = $this->makeRole('branch-manager', 8);
        $newRole = $this->makeRole('pharmacist', 6);

        $this->roles->method('findById')->willReturnOnConsecutiveCalls($actorRole, $newRole);
        $this->roles->method('getCurrentRoles')->willReturn([]);
        $this->roles->method('getExclusions')->willReturn([]);
        $this->roles->expects($this->once())->method('assignRole');
        $this->events->expects($this->once())->method('publish');

        $cmd = new AssignRoleCommand(
            targetUserId: UserId::generate()->value,
            roleId: $newRole->id->value,
            branchId: null,
            actorUserId: UserId::generate()->value,
            actorRoleId: $actorRole->id->value,
        );

        $this->useCase->execute($cmd);

        $this->addToAssertionCount(1);
    }

    public function test_throws_hierarchy_violation_when_target_level_greater_than_actor(): void
    {
        $actorRole = $this->makeRole('pharmacist', 6);
        $newRole = $this->makeRole('branch-manager', 8);

        $this->roles->method('findById')->willReturnOnConsecutiveCalls($actorRole, $newRole);
        $this->roles->method('getCurrentRoles')->willReturn([]);

        $this->expectException(HierarchyViolationException::class);

        $cmd = new AssignRoleCommand(
            UserId::generate()->value,
            $newRole->id->value, null,
            UserId::generate()->value,
            $actorRole->id->value,
        );
        $this->useCase->execute($cmd);
    }

    public function test_throws_max_roles_exceeded_when_user_has_max_roles(): void
    {
        $actorRole = $this->makeRole('super-admin', 10); // FIX: era 4
        $newRole = $this->makeRole('pharmacist', 6);   // FIX: era 6, ok
        $role1 = $this->makeRole('cashier', 4);
        $role2 = $this->makeRole('purchasing', 4);
        $role3 = $this->makeRole('auditor', 2);

        $this->roles->method('findById')->willReturnOnConsecutiveCalls($actorRole, $newRole);
        $this->roles->method('getCurrentRoles')->willReturn([$role1, $role2, $role3]);
        $this->roles->method('getExclusions')->willReturn([]);

        $this->expectException(MaxRolesExceededException::class);

        $cmd = new AssignRoleCommand(
            UserId::generate()->value,
            $newRole->id->value, null,
            UserId::generate()->value,
            $actorRole->id->value,
        );
        $this->useCase->execute($cmd);
    }

    public function test_throws_branch_required_exception_when_branch_scoped_role_missing_branch(): void
    {
        $actorRole = $this->makeRole('branch-manager', 8);
        $newRole = $this->makeRole('pharmacist', 6, true);

        $this->roles->method('findById')->willReturnOnConsecutiveCalls($actorRole, $newRole);
        $this->roles->method('getCurrentRoles')->willReturn([]);
        $this->roles->method('getExclusions')->willReturn([]);

        $this->expectException(BranchRequiredException::class);

        $cmd = new AssignRoleCommand(
            UserId::generate()->value,
            $newRole->id->value, null,
            UserId::generate()->value,
            $actorRole->id->value,
        );
        $this->useCase->execute($cmd);
    }

    public function test_throws_super_admin_exclusive_exception_for_super_admin_with_another_role(): void
    {
        $actorRole = $this->makeRole('super-admin', 10);
        $newRole = $this->makeRole('super-admin', 10);
        $existing = $this->makeRole('pharmacist', 6);

        $this->roles->method('findById')->willReturnOnConsecutiveCalls($actorRole, $newRole);
        $this->roles->method('getCurrentRoles')->willReturn([$existing]);
        $this->roles->method('getExclusions')->willReturn([]);

        $this->expectException(SuperAdminIsExclusiveException::class);

        $cmd = new AssignRoleCommand(
            UserId::generate()->value,
            $newRole->id->value, null,
            UserId::generate()->value,
            $actorRole->id->value,
        );
        $this->useCase->execute($cmd);
    }

    public function test_publishes_role_assigned_event_on_success(): void
    {
        $actorRole = $this->makeRole('branch-manager', 8);
        $newRole = $this->makeRole('pharmacist', 6);

        $this->roles->method('findById')->willReturnOnConsecutiveCalls($actorRole, $newRole);
        $this->roles->method('getCurrentRoles')->willReturn([]);
        $this->roles->method('getExclusions')->willReturn([]);
        $this->roles->method('assignRole');

        $this->events->expects($this->once())->method('publish');

        $cmd = new AssignRoleCommand(
            UserId::generate()->value,
            $newRole->id->value, null,
            UserId::generate()->value,
            $actorRole->id->value,
        );
        $this->useCase->execute($cmd);
    }
}
