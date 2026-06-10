<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/Exception/LaboratoryNotFoundException.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\Exception;

final class LaboratoryNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Laboratorio no encontrado: {$id}");
    }
}
