<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Service/CacheServiceContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Service;

use PharmaControl\Auth\Domain\ValueObject\SessionId;

interface CacheServiceContract
{
    public function getSession(SessionId $id): ?array;

    public function setSession(SessionId $id, array $data, int $ttlSeconds): void;

    public function invalidateSession(SessionId $id): void;
}
