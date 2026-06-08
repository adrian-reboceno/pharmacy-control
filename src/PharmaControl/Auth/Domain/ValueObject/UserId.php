<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/UserId.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

use PharmaControl\Shared\ValueObject\Uuid;

final readonly class UserId extends Uuid {}
