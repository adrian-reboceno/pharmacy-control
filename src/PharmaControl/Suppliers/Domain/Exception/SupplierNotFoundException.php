<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\Exception;

final class SupplierNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Proveedor no encontrado: {$id}");
    }
}
