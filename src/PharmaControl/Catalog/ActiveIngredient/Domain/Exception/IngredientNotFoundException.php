<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\Exception;

final class IngredientNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Ingrediente activo no encontrado: {$id}");
    }
}
