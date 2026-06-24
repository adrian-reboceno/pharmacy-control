<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\GetProducts;

final readonly class GetProductsQuery
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $type = null,
        public readonly ?string $statusId = null,
        public readonly ?string $categoryId = null,
        public readonly ?string $laboratoryId = null,
        public readonly ?string $saleCondition = null,
        public readonly ?bool $manageLots = null,
        public readonly ?bool $isActive = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}
}
