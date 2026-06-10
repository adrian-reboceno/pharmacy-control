<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/UseCase/UpdateCategory/UpdateCategoryCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\UseCase\UpdateCategory;

final readonly class UpdateCategoryCommand
{
    public function __construct(
        public readonly string  $id,
        public readonly ?string $parentId,
        public readonly string  $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        public readonly string  $actorUserId,
    ) {}
}
