<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\UseCase\GetIngredients;

use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;

final class GetIngredientsUseCase
{
    public function __construct(
        private readonly IngredientRepositoryContract $repository,
    ) {}

    public function __invoke(GetIngredientsQuery $query): array
    {
        $result = $this->repository->findAll([
            'search' => $query->search,
            'is_active' => $query->isActive,
            'per_page' => $query->perPage,
            'page' => $query->page,
        ]);

        return [
            'data' => array_map(
                fn ($ingredient) => IngredientDTO::fromDomain($ingredient),
                $result['data']
            ),
            'total' => $result['total'],
            'per_page' => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page' => $result['last_page'],
        ];
    }
}
