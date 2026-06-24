<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Exception;

final class LocationMustBePositionException extends \DomainException
{
    public function __construct(string $locationId)
    {
        parent::__construct("La ubicación {$locationId} debe ser de nivel POSICIÓN (level=4).");
    }
}
