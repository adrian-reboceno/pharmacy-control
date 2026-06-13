<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\Exception;

final class DuplicateRfcException extends \DomainException
{
    public function __construct(string $rfc)
    {
        parent::__construct("Ya existe un proveedor con el RFC: {$rfc}");
    }
}
