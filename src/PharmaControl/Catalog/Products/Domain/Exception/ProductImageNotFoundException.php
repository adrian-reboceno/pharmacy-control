<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Exception;

final class ProductImageNotFoundException extends \DomainException
{
    public function __construct(string $imageId)
    {
        parent::__construct("Imagen no encontrada: {$imageId}");
    }
}
