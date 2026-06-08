<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/UserStatus.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

enum UserStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case LOCKED = 'LOCKED';
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';
}
