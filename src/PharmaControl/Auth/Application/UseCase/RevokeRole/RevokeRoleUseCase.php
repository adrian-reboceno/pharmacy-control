<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/RevokeRole/RevokeRoleUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\RevokeRole;

use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Event\RoleRevoked;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class RevokeRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryContract $roles,
        private readonly SessionRepositoryContract $sessions,
        private readonly EventPublisherContract $events,
    ) {}

    public function execute(RevokeRoleCommand $command): void
    {
        $targetUserId = new UserId($command->targetUserId);
        $roleId = new RoleId($command->roleId);
        $actorUserId = new UserId($command->actorUserId);

        $currentRoles = $this->roles->getCurrentRoles($targetUserId);
        $hasRole = false;
        foreach ($currentRoles as $r) {
            if ($r->id->equals($roleId)) {
                $hasRole = true;
                break;
            }
        }

        if (! $hasRole) {
            throw new \RuntimeException('El usuario no tiene ese rol asignado.', 404);
        }

        $this->roles->revokeRole($targetUserId, $roleId);
        $this->sessions->revokeAllByUser($targetUserId);

        $this->events->publish(new RoleRevoked(
            $targetUserId,
            $roleId,
            $actorUserId,
            new \DateTimeImmutable,
        ));
    }
}
