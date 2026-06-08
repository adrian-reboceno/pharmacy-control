<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Policy/LockoutPolicy.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Policy;

final class LockoutPolicy
{
    public const MAX_ATTEMPTS = 5;

    public const DURATION_MIN = 15;

    public const TOTP_MAX = 3;

    public const TOTP_DURATION = 15;

    public function isThresholdReached(int $attempts): bool
    {
        return $attempts >= self::MAX_ATTEMPTS;
    }

    public function calculateDuration(int $previousLockouts): int
    {
        if ($previousLockouts <= 0) {
            return self::DURATION_MIN;
        }

        // Doubles with each reincidence, capped at 24 hours
        return min((int) (self::DURATION_MIN * (2 ** $previousLockouts)), 1440);
    }
}
