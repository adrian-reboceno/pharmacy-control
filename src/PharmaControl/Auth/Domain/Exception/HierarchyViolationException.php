<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/HierarchyViolationException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class HierarchyViolationException extends DomainException
{
    public function __construct(int $targetLevel, int $actorLevel)
    {
        parent::__construct(
            "No se puede asignar un rol de nivel {$targetLevel} siendo nivel {$actorLevel}.",
            403
        );
    }
}
