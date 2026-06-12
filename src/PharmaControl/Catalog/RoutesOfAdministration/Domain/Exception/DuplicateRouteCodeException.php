<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception;

final class DuplicateRouteCodeException extends \DomainException
{
    public function __construct(string $code)
    {
        parent::__construct("Ya existe una vía de administración con el código: {$code}");
    }
}
