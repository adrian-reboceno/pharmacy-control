<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/SessionId.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

use PharmaControl\Shared\ValueObject\Uuid;

final readonly class SessionId extends Uuid {}
