<?php
declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\DTO;

final readonly class ProductImageDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly bool   $isPrimary,
        public readonly int    $sortOrder,
    ) {}
}
