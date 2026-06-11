<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception;

final class DuplicateUnitNameException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Ya existe una unidad con el nombre: {$name}");
    }
}
