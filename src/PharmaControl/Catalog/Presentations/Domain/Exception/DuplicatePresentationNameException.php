<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\Exception;

final class DuplicatePresentationNameException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Ya existe una presentación con el nombre: {$name}");
    }
}
