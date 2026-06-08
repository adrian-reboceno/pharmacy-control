<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Exception/DuplicateRoleAssignmentException.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Exception;

use PharmaControl\Shared\Exception\DomainException;

final class DuplicateRoleAssignmentException extends DomainException
{
    public function __construct(string $roleName, ?string $branchId)
    {
        $scope = $branchId ? " en la sucursal '{$branchId}'" : ' (global)';
        parent::__construct("El rol '{$roleName}' ya está asignado{$scope}.", 409);
    }
}
