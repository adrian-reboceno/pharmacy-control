<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/UnauthorizedRoleException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class UnauthorizedRoleException extends DomainException
{
    public function __construct(string $roleId)
    {
        parent::__construct("El usuario no tiene asignado el rol '{$roleId}'.", 403);
    }
}
