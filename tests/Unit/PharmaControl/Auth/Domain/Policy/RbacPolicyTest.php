<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Domain/Policy/RbacPolicyTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Domain\Policy;

use PharmaControl\Auth\Domain\Exception\HierarchyViolationException;
use PharmaControl\Auth\Domain\Exception\MaxRolesExceededException;
use PharmaControl\Auth\Domain\Exception\SoDViolationException;
use PharmaControl\Auth\Domain\Exception\SuperAdminIsExclusiveException;
use PharmaControl\Auth\Domain\Model\Role;
use PharmaControl\Auth\Domain\Policy\RbacPolicy;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PHPUnit\Framework\TestCase;

final class RbacPolicyTest extends TestCase
{
    private RbacPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new RbacPolicy;
    }

    private function makeRole(string $name, int $level, bool $branchScoped = false): Role
    {
        return Role::create(RoleId::generate(), $name, ucfirst($name), $level, $branchScoped);
    }

    public function test_assert_hierarchy_passes_when_target_level_lte_actor_level(): void
    {
        $target = $this->makeRole('pharmacist', 6);
        $actor = $this->makeRole('branch-manager', 8);

        $this->policy->assertHierarchy($target, $actor);

        $this->addToAssertionCount(1);
    }

    public function test_assert_hierarchy_throws_when_target_level_gt_actor(): void
    {
        $target = $this->makeRole('branch-manager', 8);
        $actor = $this->makeRole('pharmacist', 6);

        $this->expectException(HierarchyViolationException::class);
        $this->policy->assertHierarchy($target, $actor);
    }

    public function test_assert_no_sod_conflict_throws_on_cashier_auditor(): void
    {
        $cashierId = RoleId::generate();
        $auditorId = RoleId::generate();

        $this->expectException(SoDViolationException::class);
        $this->policy->assertNoSoDConflict(
            [$cashierId],
            [$cashierId],
            'auditor',
            [$cashierId->value => 'cashier']
        );
    }

    public function test_assert_no_sod_conflict_throws_on_pharmacist_purchasing(): void
    {
        $pharmacistId = RoleId::generate();

        $this->expectException(SoDViolationException::class);
        $this->policy->assertNoSoDConflict(
            [$pharmacistId],
            [$pharmacistId],
            'purchasing',
            [$pharmacistId->value => 'pharmacist']
        );
    }

    public function test_assert_no_sod_conflict_passes_with_compatible_roles(): void
    {
        $cashierId = RoleId::generate();
        $otherId = RoleId::generate();

        $this->policy->assertNoSoDConflict([$cashierId], [$otherId], 'pharmacist', []);

        $this->addToAssertionCount(1);
    }

    public function test_assert_max_roles_throws_when_limit_reached(): void
    {
        $this->expectException(MaxRolesExceededException::class);
        $this->policy->assertMaxRoles(3, 3);
    }

    public function test_assert_max_roles_passes_when_under_limit(): void
    {
        $this->policy->assertMaxRoles(2, 3);

        $this->addToAssertionCount(1);
    }

    public function test_assert_super_admin_exclusive_throws_when_super_admin_gets_second_role(): void
    {
        $existingRole = $this->makeRole('pharmacist', 6);
        $superAdmin = $this->makeRole('super-admin', 10);

        $this->expectException(SuperAdminIsExclusiveException::class);
        $this->policy->assertSuperAdminExclusive([$existingRole], $superAdmin);
    }

    public function test_assert_super_admin_exclusive_throws_when_role_added_to_super_admin_user(): void
    {
        $superAdmin = $this->makeRole('super-admin', 10);
        $newRole = $this->makeRole('pharmacist', 6);

        $this->expectException(SuperAdminIsExclusiveException::class);
        $this->policy->assertSuperAdminExclusive([$superAdmin], $newRole);
    }
}
