<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/Exception/CategoryCycleException.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\Exception;

final class CategoryCycleException extends \DomainException
{
    public function __construct(string $categoryId, string $parentId)
    {
        parent::__construct(
            "No se puede asignar la categoría '{$parentId}' como padre de '{$categoryId}' porque crearía un ciclo."
        );
    }
}
