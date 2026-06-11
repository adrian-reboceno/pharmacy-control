<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception;

final class UnitNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Unidad de medida no encontrada: {$id}");
    }
}
