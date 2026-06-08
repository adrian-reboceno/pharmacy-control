<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/AccountLockedException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Exception\DomainException;

final class AccountLockedException extends DomainException
{
    public function __construct(
        public readonly UserId $userId,
        public readonly ?\DateTimeImmutable $lockedUntil,
    ) {
        $until = $lockedUntil?->format('Y-m-d H:i:s') ?? 'indefinidamente';
        parent::__construct("La cuenta está bloqueada hasta {$until}.", 423);
    }
}
