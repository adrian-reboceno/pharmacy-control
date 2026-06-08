<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/RevokeRole/RevokeRoleCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\RevokeRole;

final readonly class RevokeRoleCommand
{
    public function __construct(
        public readonly string $targetUserId,
        public readonly string $roleId,
        public readonly string $actorUserId,
    ) {}
}
