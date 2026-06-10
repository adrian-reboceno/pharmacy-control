<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/Exception/ClassificationNotFoundException.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\Exception;

final class ClassificationNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Clasificación no encontrada: {$id}");
    }
}
