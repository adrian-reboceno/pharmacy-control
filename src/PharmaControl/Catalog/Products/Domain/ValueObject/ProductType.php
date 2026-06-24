<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\ValueObject;

enum ProductType: string
{
    case GENERIC = 'GENERIC';
    case BRANDED = 'BRANDED';

    public function label(): string
    {
        return match ($this) {
            self::GENERIC => 'Genérico',
            self::BRANDED => 'Marca',
        };
    }

    public function requiresLaboratory(): bool
    {
        return $this === self::BRANDED;
    }
}
