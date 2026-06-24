<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Exception;

final class ProductNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Producto no encontrado: {$id}");
    }
}
