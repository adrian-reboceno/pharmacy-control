<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/Logout/LogoutUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\Logout;

use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;
use PharmaControl\Auth\Domain\Event\SessionRevoked;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class LogoutUseCase
{
    public function __construct(
        private readonly SessionRepositoryContract $sessions,
        private readonly TokenServiceContract $tokens,
        private readonly EventPublisherContract $events,
    ) {}

    public function execute(LogoutCommand $command): void
    {
        $sessionId = new SessionId($command->sessionId);

        $this->tokens->blacklist($command->jti, $command->tokenTtlRemaining);
        $this->sessions->revokeBySession($sessionId);

        $this->events->publish(new SessionRevoked(
            $sessionId,
            new UserId($command->userId),
            new UserId($command->userId),
            new \DateTimeImmutable,
        ));
    }
}
