<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception;

final class DuplicateUnitSymbolException extends \DomainException
{
    public function __construct(string $symbol)
    {
        parent::__construct("Ya existe una unidad con el símbolo: {$symbol}");
    }
}
