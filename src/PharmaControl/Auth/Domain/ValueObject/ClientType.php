<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/ClientType.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

enum ClientType: string
{
    case WEB = 'WEB';
    case MOBILE = 'MOBILE';
}
