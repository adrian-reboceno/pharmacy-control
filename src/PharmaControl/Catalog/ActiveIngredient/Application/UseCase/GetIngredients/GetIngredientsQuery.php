<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\UseCase\GetIngredients;

final readonly class GetIngredientsQuery
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?bool $isActive = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}
}
