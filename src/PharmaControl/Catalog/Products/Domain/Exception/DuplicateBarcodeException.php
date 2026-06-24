<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Exception;

final class DuplicateBarcodeException extends \DomainException
{
    public function __construct(string $barcode)
    {
        parent::__construct("Ya existe un producto con el código de barras: {$barcode}");
    }
}
