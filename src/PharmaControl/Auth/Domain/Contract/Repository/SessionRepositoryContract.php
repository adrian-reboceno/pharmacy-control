<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Repository/SessionRepositoryContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Repository;

use PharmaControl\Auth\Domain\Model\UserSession;
use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;

interface SessionRepositoryContract
{
    public function save(UserSession $session): void;

    public function findByAccessTokenHash(string $hash): ?UserSession;

    public function findByRefreshTokenHash(string $hash): ?UserSession;

    public function findActiveByUserAndClient(UserId $userId, ClientType $type): ?UserSession;

    public function revokeAllByUser(UserId $userId): void;

    public function revokeBySession(SessionId $id): void;
}
