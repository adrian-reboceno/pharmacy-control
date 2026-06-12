<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception;

final class RouteNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Vía de administración no encontrada: {$id}");
    }
}
