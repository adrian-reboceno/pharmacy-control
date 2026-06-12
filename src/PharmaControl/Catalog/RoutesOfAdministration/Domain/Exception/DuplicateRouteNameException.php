<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception;

final class DuplicateRouteNameException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Ya existe una vía de administración con el nombre: {$name}");
    }
}
