<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\Exception;

final class DuplicateDciCodeException extends \DomainException
{
    public function __construct(string $code)
    {
        parent::__construct("Ya existe un ingrediente activo con el código DCI: {$code}");
    }
}
