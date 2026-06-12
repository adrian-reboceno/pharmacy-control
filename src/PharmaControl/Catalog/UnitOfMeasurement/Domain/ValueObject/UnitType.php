<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject;

enum UnitType: string
{
    case QUANTITY = 'QUANTITY';
    case CONCENTRATION = 'CONCENTRATION';

    public function label(): string
    {
        return match ($this) {
            self::QUANTITY => 'Cantidad / Presentación',
            self::CONCENTRATION => 'Concentración / Dosis',
        };
    }
}
