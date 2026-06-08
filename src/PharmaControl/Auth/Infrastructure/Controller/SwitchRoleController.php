<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/SwitchRoleController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Application\UseCase\SwitchRole\SwitchRoleCommand;
use PharmaControl\Auth\Application\UseCase\SwitchRole\SwitchRoleUseCase;

final class SwitchRoleController
{
    public function __construct(private readonly SwitchRoleUseCase $useCase) {}

    public function __invoke(array $data): AuthTokenDTO
    {
        return $this->useCase->execute(new SwitchRoleCommand(
            userId: $data['user_id'],
            sessionId: $data['session_id'],
            targetRoleId: $data['target_role_id'],
            branchId: $data['branch_id'] ?? null,
            currentJti: $data['current_jti'],
            currentTokenTtl: $data['current_token_ttl'],
        ));
    }
}
