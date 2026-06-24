<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\GetProduct;

final readonly class GetProductQuery
{
    public function __construct(
        public readonly string $id,
    ) {}
}
