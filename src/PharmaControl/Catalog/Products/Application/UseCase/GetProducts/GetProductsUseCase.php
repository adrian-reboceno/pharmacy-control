<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\GetProducts;

use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;

final class GetProductsUseCase
{
    public function __construct(
        private readonly ProductRepositoryContract $productRepo,
    ) {}

    public function __invoke(GetProductsQuery $query): array
    {
        $result = $this->productRepo->findAll([
            'search'        => $query->search,
            'type'          => $query->type,
            'status_id'     => $query->statusId,
            'category_id'   => $query->categoryId,
            'laboratory_id' => $query->laboratoryId,
            'sale_condition' => $query->saleCondition,
            'manage_lots'   => $query->manageLots,
            'is_active'     => $query->isActive,
            'per_page'      => $query->perPage,
            'page'          => $query->page,
        ]);

        return [
            'data'         => array_map(
                fn ($product) => ProductDTO::fromDomain($product),
                $result['data']
            ),
            'total'        => $result['total'],
            'per_page'     => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page'    => $result['last_page'],
        ];
    }
}
