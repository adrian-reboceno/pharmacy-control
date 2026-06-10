<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/UseCase/CreateCategory/CreateCategoryCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\UseCase\CreateCategory;

final readonly class CreateCategoryCommand
{
    public function __construct(
        public readonly ?string $parentId,
        public readonly string  $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        public readonly string  $actorUserId,
    ) {}
}
