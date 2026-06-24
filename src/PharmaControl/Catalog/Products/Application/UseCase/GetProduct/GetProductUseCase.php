<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\GetProduct;

use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Exception\ProductNotFoundException;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;

final class GetProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryContract $productRepo,
    ) {}

    public function __invoke(GetProductQuery $query): ProductDTO
    {
        $product = $this->productRepo->findById(new ProductId($query->id));
        if ($product === null) {
            throw new ProductNotFoundException($query->id);
        }

        return ProductDTO::fromDomain($product);
    }
}
