<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/InvalidCredentialsException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Credenciales incorrectas.', 401);
    }
}
