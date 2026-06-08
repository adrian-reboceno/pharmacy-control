<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/MaxRolesExceededException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class MaxRolesExceededException extends DomainException
{
    public function __construct(int $max)
    {
        parent::__construct("El usuario ya tiene el máximo de {$max} roles permitidos.", 422);
    }
}
