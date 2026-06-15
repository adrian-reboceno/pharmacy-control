<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\Exception;

final class DuplicateIngredientNameException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Ya existe un ingrediente activo con el nombre: {$name}");
    }
}
