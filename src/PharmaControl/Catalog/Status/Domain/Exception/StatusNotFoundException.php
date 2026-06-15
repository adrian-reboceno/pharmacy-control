<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\Exception;

final class StatusNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Estado de producto no encontrado: {$id}");
    }
}
