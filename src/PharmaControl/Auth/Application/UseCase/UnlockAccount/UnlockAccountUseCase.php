<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/UnlockAccount/UnlockAccountUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\UnlockAccount;

use PharmaControl\Auth\Domain\Contract\Repository\AuditLogRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\NotificationServiceContract;
use PharmaControl\Auth\Domain\Model\AuditEntry;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class UnlockAccountUseCase
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly AuditLogRepositoryContract $auditLog,
        private readonly NotificationServiceContract $notifications,
        private readonly EventPublisherContract $events,
    ) {}

    public function execute(UnlockAccountCommand $command): void
    {
        $targetUserId = new UserId($command->targetUserId);
        $actorUserId = new UserId($command->actorUserId);

        $user = $this->users->findById($targetUserId);
        if ($user === null) {
            throw new \RuntimeException('Usuario no encontrado.', 404);
        }

        if (! $user->isLocked()) {
            throw new \RuntimeException('La cuenta no está bloqueada.', 422);
        }

        $user->unlock();
        $this->users->save($user);

        $this->notifications->sendUnlockEmail($user->email);

        foreach ($user->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        $this->auditLog->append(AuditEntry::create(
            userId: $actorUserId,
            userEmail: '',
            userRole: '',
            branchId: null,
            module: 'auth',
            action: 'ACCOUNT_UNLOCKED',
            entityType: 'User',
            entityId: $targetUserId->value,
            oldValues: null,
            newValues: ['status' => 'ACTIVE'],
            metadata: ['unlockedBy' => $actorUserId->value],
            ipAddress: new IpAddress('127.0.0.1'),
            userAgent: null,
            status: 'SUCCESS',
        ));
    }
}
