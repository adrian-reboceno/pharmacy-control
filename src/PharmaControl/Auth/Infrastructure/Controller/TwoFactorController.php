<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/TwoFactorController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Application\UseCase\ConfigureTwoFactor\ConfigureTwoFactorCommand;
use PharmaControl\Auth\Application\UseCase\ConfigureTwoFactor\ConfigureTwoFactorUseCase;

final class TwoFactorController
{
    public function __construct(private readonly ConfigureTwoFactorUseCase $useCase) {}

    public function setup(array $data): array
    {
        return $this->useCase->initiate(new ConfigureTwoFactorCommand(
            userId: $data['user_id'],
        ));
    }

    public function verify(array $data): array
    {
        return $this->useCase->confirm(new ConfigureTwoFactorCommand(
            userId: $data['user_id'],
            totpCode: $data['totp_code'],
            secret: $data['secret'],
        ));
    }
}
