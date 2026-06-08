<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/AssignRole/AssignRoleCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\AssignRole;

final readonly class AssignRoleCommand
{
    public function __construct(
        public readonly string $targetUserId,
        public readonly string $roleId,
        public readonly ?string $branchId,
        public readonly string $actorUserId,
        public readonly string $actorRoleId,
    ) {}
}
