<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/SwitchRole/SwitchRoleUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\SwitchRole;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;
use PharmaControl\Auth\Domain\Event\RoleSwitched;
use PharmaControl\Auth\Domain\Exception\BranchRequiredException;
use PharmaControl\Auth\Domain\Exception\UnauthorizedRoleException;
use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\ValueObject\BranchId;

final class SwitchRoleUseCase
{
    public function __construct(
        private readonly SessionRepositoryContract $sessions,
        private readonly RoleRepositoryContract $roles,
        private readonly TokenServiceContract $tokens,
        private readonly EventPublisherContract $events,
    ) {}

    public function execute(SwitchRoleCommand $command): AuthTokenDTO
    {
        $userId = new UserId($command->userId);
        $sessionId = new SessionId($command->sessionId);
        $targetRoleId = new RoleId($command->targetRoleId);
        $branchId = $command->branchId ? new BranchId($command->branchId) : null;

        $userRoles = $this->roles->getCurrentRoles($userId);
        $hasRole = false;
        foreach ($userRoles as $role) {
            if ($role->id->equals($targetRoleId)) {
                $hasRole = true;
                break;
            }
        }
        if (! $hasRole) {
            throw new UnauthorizedRoleException($command->targetRoleId);
        }

        $targetRole = $this->roles->findById($targetRoleId);
        if ($targetRole->branchScoped && $branchId === null) {
            throw new BranchRequiredException($targetRole->name);
        }

        $session = $this->sessions->findByAccessTokenHash(
            hash('sha256', '')
        );

        $this->tokens->blacklist($command->currentJti, $command->currentTokenTtl);

        $permissions = $this->roles->resolvePermissions($targetRoleId);

        $tokenData = $this->tokens->issue(
            $userId,
            $targetRoleId,
            $branchId,
            $permissions,
            $session?->clientType ?? ClientType::WEB,
            $sessionId,
        );

        $newAccessHash = hash('sha256', $tokenData['accessToken']);

        if ($session !== null) {
            $fromRoleId = $session->getActiveRoleId();
            $session->switchRole($targetRoleId, $branchId, $newAccessHash);
            $this->sessions->save($session);

            $this->events->publish(new RoleSwitched(
                $sessionId,
                $userId,
                $fromRoleId,
                $targetRoleId,
                $branchId,
                new \DateTimeImmutable,
            ));
        }

        return new AuthTokenDTO(
            accessToken: $tokenData['accessToken'],
            refreshToken: $tokenData['refreshToken'],
            expiresIn: $tokenData['expiresIn'],
            tokenType: 'Bearer',
            requiresPasswordChange: false,
            requiresRoleSelection: false,
            availableRoles: null,
            activeRoleId: $targetRoleId->value,
            activeBranchId: $branchId?->value,
        );
    }
}
