<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Service/RedisRateLimiterService.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Service;

use Illuminate\Support\Facades\RateLimiter;
use PharmaControl\Auth\Domain\Contract\Service\RateLimiterServiceContract;

final class RedisRateLimiterService implements RateLimiterServiceContract
{
    public function hit(string $key, int $maxAttempts, int $decaySeconds): void
    {
        RateLimiter::hit($key, $decaySeconds);
    }

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    public function clear(string $key): void
    {
        RateLimiter::clear($key);
    }

    public function availableInSeconds(string $key): int
    {
        return RateLimiter::availableIn($key);
    }
}
