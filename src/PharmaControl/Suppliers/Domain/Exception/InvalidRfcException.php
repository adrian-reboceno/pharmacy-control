<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\Exception;

final class InvalidRfcException extends \DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
