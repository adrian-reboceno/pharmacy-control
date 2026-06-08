<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Model/RoleMetadata.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\RoleId;

final readonly class RoleMetadata
{
    public function __construct(
        public readonly RoleId $roleId,
        public readonly string $displayName,
        public readonly int $hierarchyLevel,
        public readonly bool $branchScoped,
    ) {}
}
