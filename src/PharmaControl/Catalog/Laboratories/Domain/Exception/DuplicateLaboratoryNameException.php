<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/Exception/DuplicateLaboratoryNameException.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\Exception;

final class DuplicateLaboratoryNameException extends \DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Ya existe un laboratorio con el nombre: {$name}");
    }
}
