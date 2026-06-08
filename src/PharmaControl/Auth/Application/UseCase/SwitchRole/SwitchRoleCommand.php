<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/SwitchRole/SwitchRoleCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\SwitchRole;

final readonly class SwitchRoleCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $sessionId,
        public readonly string $targetRoleId,
        public readonly ?string $branchId,
        public readonly string $currentJti,
        public readonly int $currentTokenTtl,
    ) {}
}
