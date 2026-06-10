<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/UseCase/DeactivateCategory/DeactivateCategoryCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\UseCase\DeactivateCategory;

final readonly class DeactivateCategoryCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
