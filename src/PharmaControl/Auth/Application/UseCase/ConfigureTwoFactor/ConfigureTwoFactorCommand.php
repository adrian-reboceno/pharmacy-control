<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/ConfigureTwoFactor/ConfigureTwoFactorCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\ConfigureTwoFactor;

final readonly class ConfigureTwoFactorCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $totpCode = null,
        public readonly ?string $secret = null,
    ) {}
}
