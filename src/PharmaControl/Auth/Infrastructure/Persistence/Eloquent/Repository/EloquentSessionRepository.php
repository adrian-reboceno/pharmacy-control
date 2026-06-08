<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Repository/EloquentSessionRepository.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Model\UserSession;
use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUserSession;
use PharmaControl\Auth\Infrastructure\Persistence\Mapper\SessionMapper;

final class EloquentSessionRepository implements SessionRepositoryContract
{
    public function __construct(private readonly SessionMapper $mapper) {}

    public function save(UserSession $session): void
    {
        $data = $this->mapper->toPersistence($session);
        EloquentUserSession::updateOrCreate(['id' => $data['id']], $data);
    }

    public function findByAccessTokenHash(string $hash): ?UserSession
    {
        $model = EloquentUserSession::where('access_token_hash', $hash)->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByRefreshTokenHash(string $hash): ?UserSession
    {
        $model = EloquentUserSession::where('refresh_token_hash', $hash)->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findActiveByUserAndClient(UserId $userId, ClientType $type): ?UserSession
    {
        $model = EloquentUserSession::where('user_id', $userId->value)
            ->where('client_type', $type->value)
            ->whereNull('revoked_at')
            ->where('refresh_expires_at', '>', now())
            ->latest('created_at')
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function revokeAllByUser(UserId $userId): void
    {
        EloquentUserSession::where('user_id', $userId->value)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeBySession(SessionId $id): void
    {
        EloquentUserSession::where('id', $id->value)
            ->update(['revoked_at' => now()]);
    }
}
