<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/BranchRequiredException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class BranchRequiredException extends DomainException
{
    public function __construct(string $roleName)
    {
        parent::__construct(
            "El rol '{$roleName}' requiere una sucursal (branch_id) para ser asignado.",
            422
        );
    }
}
