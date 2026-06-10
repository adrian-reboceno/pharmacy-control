<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/Exception/CategoryNotFoundException.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\Exception;

final class CategoryNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Categoría no encontrada: {$id}");
    }
}
