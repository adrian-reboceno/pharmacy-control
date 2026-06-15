<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\Exception;

final class DuplicateStatusNameException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Ya existe un estado con el nombre: {$name}");
    }
}
