<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/Exception/DuplicateCategorySlugException.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\Exception;

final class DuplicateCategorySlugException extends \DomainException
{
    public function __construct(string $slug)
    {
        parent::__construct("Ya existe una categoría con el slug: {$slug}");
    }
}
