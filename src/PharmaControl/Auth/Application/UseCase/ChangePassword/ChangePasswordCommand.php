<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/ChangePassword/ChangePasswordCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\ChangePassword;

final readonly class ChangePasswordCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $currentPassword,
        public readonly string $newPassword,
        public readonly ?string $currentSessionId = null,
    ) {}
}
