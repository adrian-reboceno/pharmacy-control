<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\Exception;

final class DuplicateCasNumberException extends \DomainException
{
    public function __construct(string $cas)
    {
        parent::__construct("Ya existe un ingrediente activo con el número CAS: {$cas}");
    }
}
