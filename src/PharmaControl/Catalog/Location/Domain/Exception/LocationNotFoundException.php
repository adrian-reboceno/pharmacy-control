<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\Exception;

final class LocationNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Ubicación no encontrada: {$id}");
    }
}
