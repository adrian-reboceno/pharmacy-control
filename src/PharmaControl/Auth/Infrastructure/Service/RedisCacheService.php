<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Service/RedisCacheService.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Service;

use Illuminate\Support\Facades\Cache;
use PharmaControl\Auth\Domain\Contract\Service\CacheServiceContract;
use PharmaControl\Auth\Domain\ValueObject\SessionId;

final class RedisCacheService implements CacheServiceContract
{
    private string $prefix = 'session:';

    public function getSession(SessionId $id): ?array
    {
        $value = Cache::store('redis')->get($this->prefix.$id->value);

        return is_array($value) ? $value : null;
    }

    public function setSession(SessionId $id, array $data, int $ttlSeconds): void
    {
        Cache::store('redis')->put($this->prefix.$id->value, $data, $ttlSeconds);
    }

    public function invalidateSession(SessionId $id): void
    {
        Cache::store('redis')->forget($this->prefix.$id->value);
    }
}
