<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\Exception;

final class PresentationNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Presentación no encontrada: {$id}");
    }
}
