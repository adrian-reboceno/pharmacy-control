<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Entity;

final class ProductIngredient
{
    public function __construct(
        public readonly string $ingredientId,
        public readonly string $concentration,
        public readonly string $concentrationUnit,
    ) {
        if (empty(trim($concentration))) {
            throw new \InvalidArgumentException('La concentración no puede estar vacía.');
        }
        if (empty(trim($concentrationUnit))) {
            throw new \InvalidArgumentException('La unidad de concentración no puede estar vacía.');
        }
    }
}
