<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/RefreshTokenController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Application\UseCase\RefreshToken\RefreshTokenCommand;
use PharmaControl\Auth\Application\UseCase\RefreshToken\RefreshTokenUseCase;

final class RefreshTokenController
{
    public function __construct(private readonly RefreshTokenUseCase $useCase) {}

    public function __invoke(array $data): AuthTokenDTO
    {
        return $this->useCase->execute(new RefreshTokenCommand(
            refreshToken: $data['refresh_token'],
            ipAddress: $data['ip_address'],
            userAgent: $data['user_agent'] ?? null,
        ));
    }
}
