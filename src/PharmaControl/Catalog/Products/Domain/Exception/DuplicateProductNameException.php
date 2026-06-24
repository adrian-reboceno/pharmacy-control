<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Exception;

final class DuplicateProductNameException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Ya existe un producto con el nombre: {$name}");
    }
}
