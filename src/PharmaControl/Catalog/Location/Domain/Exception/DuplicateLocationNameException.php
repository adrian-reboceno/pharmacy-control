<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\Exception;

final class DuplicateLocationNameException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Ya existe una ubicación con el nombre '{$name}' en este nivel.");
    }
}
