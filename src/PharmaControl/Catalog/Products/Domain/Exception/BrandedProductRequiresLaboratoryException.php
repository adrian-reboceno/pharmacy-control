<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Exception;

final class BrandedProductRequiresLaboratoryException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Un producto de marca (BRANDED) requiere un laboratorio asignado.');
    }
}
