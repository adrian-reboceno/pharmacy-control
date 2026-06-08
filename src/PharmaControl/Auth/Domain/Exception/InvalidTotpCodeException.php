<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/InvalidTotpCodeException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class InvalidTotpCodeException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Código de autenticación de dos factores incorrecto.', 401);
    }
}
