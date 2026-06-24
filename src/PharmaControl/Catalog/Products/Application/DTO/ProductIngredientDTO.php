<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\DTO;

use PharmaControl\Catalog\Products\Domain\Entity\ProductIngredient;

final readonly class ProductIngredientDTO
{
    public function __construct(
        public readonly string $ingredientId,
        public readonly string $concentration,
        public readonly string $concentrationUnit,
    ) {}

    public static function fromEntity(ProductIngredient $entity): self
    {
        return new self(
            ingredientId:      $entity->ingredientId,
            concentration:     $entity->concentration,
            concentrationUnit: $entity->concentrationUnit,
        );
    }
}
