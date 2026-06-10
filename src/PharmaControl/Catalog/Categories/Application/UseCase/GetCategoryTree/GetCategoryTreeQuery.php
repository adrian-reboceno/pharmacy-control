<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/UseCase/GetCategoryTree/GetCategoryTreeQuery.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\UseCase\GetCategoryTree;

final readonly class GetCategoryTreeQuery
{
    public function __construct(
        public readonly ?bool $isActive = null,
    ) {}
}
