<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/UnlockAccount/UnlockAccountCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\UnlockAccount;

final readonly class UnlockAccountCommand
{
    public function __construct(
        public readonly string $targetUserId,
        public readonly string $actorUserId,
    ) {}
}
