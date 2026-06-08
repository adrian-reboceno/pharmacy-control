<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/RefreshToken/RefreshTokenUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\RefreshToken;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;

final class RefreshTokenUseCase
{
    public function __construct(
        private readonly SessionRepositoryContract $sessions,
        private readonly RoleRepositoryContract $roles,
        private readonly TokenServiceContract $tokens,
        private readonly EventPublisherContract $events,
    ) {}

    public function execute(RefreshTokenCommand $command): AuthTokenDTO
    {
        $refreshHash = hash('sha256', $command->refreshToken);
        $session = $this->sessions->findByRefreshTokenHash($refreshHash);

        if ($session === null || $session->getRevokedAt() !== null) {
            throw new \RuntimeException('Refresh token inválido o revocado.', 401);
        }

        if ($session->isRefreshExpired()) {
            throw new \RuntimeException('Refresh token expirado.', 401);
        }

        $permissions = $this->roles->resolvePermissions($session->getActiveRoleId());

        $tokenData = $this->tokens->issue(
            $session->userId,
            $session->getActiveRoleId(),
            $session->getActiveBranchId(),
            $permissions,
            $session->clientType,
            $session->id,
        );

        $newAccessHash = hash('sha256', $tokenData['accessToken']);
        $newRefreshHash = hash('sha256', $tokenData['refreshToken']);

        $session->rotateRefreshToken($newRefreshHash, $newAccessHash);
        $this->sessions->save($session);

        return new AuthTokenDTO(
            accessToken: $tokenData['accessToken'],
            refreshToken: $tokenData['refreshToken'],
            expiresIn: $tokenData['expiresIn'],
            tokenType: 'Bearer',
            requiresPasswordChange: false,
            requiresRoleSelection: false,
            availableRoles: null,
            activeRoleId: $session->getActiveRoleId()->value,
            activeBranchId: $session->getActiveBranchId()?->value,
        );
    }
}
