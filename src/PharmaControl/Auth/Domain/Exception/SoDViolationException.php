<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/SoDViolationException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class SoDViolationException extends DomainException
{
    public function __construct(string $roleA, string $roleB)
    {
        parent::__construct(
            "Violación de Separación de Obligaciones: '{$roleA}' y '{$roleB}' son mutuamente excluyentes.",
            409
        );
    }
}
