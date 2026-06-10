<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/ValueObject/PrescriptionType.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\ValueObject;

enum PrescriptionType: string
{
    case CON_CODIGO_BARRAS = 'CON_CODIGO_BARRAS';
    case NORMAL = 'NORMAL';
    case SIN_RECETA = 'SIN_RECETA';

    public function label(): string
    {
        return match ($this) {
            self::CON_CODIGO_BARRAS => 'Con código de barras',
            self::NORMAL => 'Receta normal',
            self::SIN_RECETA => 'Sin receta',
        };
    }
}
