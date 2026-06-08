<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Repository/EloquentRoleRepository.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository;

use App\Models\Role as CustomEloquentRole;
use Illuminate\Support\Facades\DB;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Model\Role;
use PharmaControl\Auth\Domain\Model\RoleMetadata;
use PharmaControl\Auth\Domain\ValueObject\PermissionName;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentRoleExclusion;
// use Spatie\Permission\Models\Role as SpatieRole;
use PharmaControl\Shared\ValueObject\BranchId;

final class EloquentRoleRepository implements RoleRepositoryContract
{
    public function findById(RoleId $id): ?Role
    {
        $spatieRole = CustomEloquentRole::with('permissions')->find($id->value);

        return $spatieRole ? $this->toRole($spatieRole) : null;
    }

    public function findByName(string $name): ?Role
    {
        $spatieRole = CustomEloquentRole::with('permissions')->where('name', $name)->first();

        return $spatieRole ? $this->toRole($spatieRole) : null;
    }

    /** @return list<Role> */
    public function getCurrentRoles(UserId $userId): array
    {

        $spatieRoles = CustomEloquentRole::with('permissions')
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_uuid', $userId->value)
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->select('roles.*')
            ->get();

        return $spatieRoles->map(fn ($r) => $this->toRole($r))->values()->all();
    }

    /** @return list<RoleId> */
    public function getExclusions(RoleId $roleId): array
    {
        $rows = EloquentRoleExclusion::where('role_a_id', $roleId->value)
            ->orWhere('role_b_id', $roleId->value)
            ->get();

        return $rows->map(function ($row) use ($roleId) {
            $otherId = $row->role_a_id === $roleId->value ? $row->role_b_id : $row->role_a_id;

            return new RoleId($otherId);
        })->values()->all();
    }

    public function getMetadata(RoleId $roleId): RoleMetadata
    {
        $row = DB::table('role_metadata')->where('role_id', $roleId->value)->first();
        if (! $row) {
            throw new \RuntimeException("Metadata no encontrada para el rol {$roleId->value}");
        }

        return new RoleMetadata(
            $roleId,
            $row->display_name,
            $row->hierarchy_level,
            (bool) $row->branch_scoped,
        );
    }

    public function assignRole(UserId $userId, RoleId $roleId, ?BranchId $branchId, UserId $assignedBy): void
    {
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId->value,
            'model_type' => 'App\\Models\\User',
            'model_uuid' => $userId->value,
            'branch_id' => $branchId?->value,
            'assigned_by' => $assignedBy->value,
            'assigned_at' => now(),
        ]);
    }

    public function revokeRole(UserId $userId, RoleId $roleId): void
    {
        DB::table('model_has_roles')
            ->where('model_uuid', $userId->value)
            ->where('role_id', $roleId->value)
            ->where('model_type', 'App\\Models\\User')
            ->delete();
    }

    /** @return list<PermissionName> */
    public function resolvePermissions(RoleId $roleId): array
    {
        $role = CustomEloquentRole::with('permissions')->find($roleId->value);
        if (! $role) {
            return [];
        }

        return $role->permissions->map(function ($p) {
            try {
                return new PermissionName($p->name);
            } catch (\InvalidArgumentException) {
                return null;
            }
        })->filter()->values()->all();
    }

    private function toRole(CustomEloquentRole $spatieRole): Role
    {
        $metadata = DB::table('role_metadata')->where('role_id', $spatieRole->id)->first();

        $permissions = $spatieRole->permissions->map(function ($p) {
            try {
                return new PermissionName($p->name);
            } catch (\InvalidArgumentException) {
                return null;
            }
        })->filter()->values()->all();

        return Role::reconstitute(
            id: new RoleId($spatieRole->id),
            name: $spatieRole->name,
            displayName: $metadata?->display_name ?? $spatieRole->name,
            hierarchyLevel: $metadata?->hierarchy_level ?? 1,
            branchScoped: (bool) ($metadata?->branch_scoped ?? false),
            permissions: $permissions,
        );
    }
}
