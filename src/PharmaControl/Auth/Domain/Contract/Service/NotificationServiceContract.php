<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Service/NotificationServiceContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Service;

use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;

interface NotificationServiceContract
{
    public function sendLockNotification(
        UserId $userId,
        Email $email,
        IpAddress $ip,
        \DateTimeImmutable $lockedUntil,
    ): void;

    public function sendUnlockEmail(Email $email): void;

    public function sendWelcomeEmail(Email $email, string $firstName, string $temporaryPassword): void;

    public function sendPasswordExpiredWarning(Email $email): void;
}
