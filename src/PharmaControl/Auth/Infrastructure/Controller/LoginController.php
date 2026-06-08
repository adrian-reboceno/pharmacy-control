<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/LoginController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Application\UseCase\Login\LoginCommand;
use PharmaControl\Auth\Application\UseCase\Login\LoginUseCase;

final class LoginController
{
    public function __construct(private readonly LoginUseCase $useCase) {}

    public function __invoke(array $data): AuthTokenDTO
    {
        return $this->useCase->execute(new LoginCommand(
            email: $data['email'],
            password: $data['password'],
            clientType: $data['client_type'] ?? 'WEB',
            ipAddress: $data['ip_address'],
            userAgent: $data['user_agent'] ?? null,
        ));
    }
}
