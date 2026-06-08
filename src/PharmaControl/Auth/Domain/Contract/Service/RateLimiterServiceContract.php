<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Service/RateLimiterServiceContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Service;

interface RateLimiterServiceContract
{
    public function hit(string $key, int $maxAttempts, int $decaySeconds): void;

    public function tooManyAttempts(string $key, int $maxAttempts): bool;

    public function clear(string $key): void;

    public function availableInSeconds(string $key): int;
}
