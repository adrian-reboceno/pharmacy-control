<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/SuperAdminIsExclusiveException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class SuperAdminIsExclusiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'El rol super-admin no puede combinarse con otros roles.',
            409
        );
    }
}
