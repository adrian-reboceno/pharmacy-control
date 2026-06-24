<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\Exception;

final class LocationMaxDepthException extends \DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
