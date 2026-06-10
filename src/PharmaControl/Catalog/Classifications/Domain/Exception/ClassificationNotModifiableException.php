<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/Exception/ClassificationNotModifiableException.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\Exception;

final class ClassificationNotModifiableException extends \DomainException
{
    public function __construct(string $field)
    {
        parent::__construct("El campo '{$field}' no puede modificarse en una clasificación LGS.");
    }
}
