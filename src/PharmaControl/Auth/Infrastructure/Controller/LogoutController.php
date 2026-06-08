<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/LogoutController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Application\UseCase\Logout\LogoutCommand;
use PharmaControl\Auth\Application\UseCase\Logout\LogoutUseCase;

final class LogoutController
{
    public function __construct(private readonly LogoutUseCase $useCase) {}

    public function __invoke(array $data): void
    {
        $this->useCase->execute(new LogoutCommand(
            userId: $data['user_id'],
            sessionId: $data['session_id'],
            jti: $data['jti'],
            tokenTtlRemaining: $data['token_ttl_remaining'],
        ));
    }
}
