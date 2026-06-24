<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\Exception;

final class LocationNotLeafException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("La ubicación '{$id}' no es una Posición (level=4). Solo las Posiciones pueden asignarse a productos.");
    }
}
