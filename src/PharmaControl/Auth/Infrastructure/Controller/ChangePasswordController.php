<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/ChangePasswordController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Application\UseCase\ChangePassword\ChangePasswordCommand;
use PharmaControl\Auth\Application\UseCase\ChangePassword\ChangePasswordUseCase;

final class ChangePasswordController
{
    public function __construct(private readonly ChangePasswordUseCase $useCase) {}

    public function __invoke(array $data): void
    {
        $this->useCase->execute(new ChangePasswordCommand(
            userId: $data['user_id'],
            currentPassword: $data['current_password'],
            newPassword: $data['new_password'],
            currentSessionId: $data['session_id'] ?? null,
        ));
    }
}
