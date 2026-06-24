<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Exception;

final class InvalidBarcodeException extends \DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
