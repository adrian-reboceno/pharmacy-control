<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/RoleController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Application\UseCase\AssignRole\AssignRoleCommand;
use PharmaControl\Auth\Application\UseCase\AssignRole\AssignRoleUseCase;
use PharmaControl\Auth\Application\UseCase\RevokeRole\RevokeRoleCommand;
use PharmaControl\Auth\Application\UseCase\RevokeRole\RevokeRoleUseCase;

final class RoleController
{
    public function __construct(
        private readonly AssignRoleUseCase $assignRole,
        private readonly RevokeRoleUseCase $revokeRole,
    ) {}

    public function assign(array $data): void
    {
        $this->assignRole->execute(new AssignRoleCommand(
            targetUserId: $data['target_user_id'],
            roleId: $data['role_id'],
            branchId: $data['branch_id'] ?? null,
            actorUserId: $data['actor_user_id'],
            actorRoleId: $data['actor_role_id'],
        ));
    }

    public function revoke(array $data): void
    {
        $this->revokeRole->execute(new RevokeRoleCommand(
            targetUserId: $data['target_user_id'],
            roleId: $data['role_id'],
            actorUserId: $data['actor_user_id'],
        ));
    }
}
