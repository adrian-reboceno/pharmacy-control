<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\ValueObject;

enum SaleCondition: string
{
    case SIN_RECETA          = 'SIN_RECETA';
    case CON_RECETA          = 'CON_RECETA';
    case CON_RECETA_RETENIDA = 'CON_RECETA_RETENIDA';

    public function label(): string
    {
        return match ($this) {
            self::SIN_RECETA          => 'Sin receta',
            self::CON_RECETA          => 'Con receta médica',
            self::CON_RECETA_RETENIDA => 'Con receta retenida',
        };
    }

    public function requiresPrescription(): bool
    {
        return $this !== self::SIN_RECETA;
    }
}
