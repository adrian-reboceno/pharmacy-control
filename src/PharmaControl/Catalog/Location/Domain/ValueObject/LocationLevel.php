<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\ValueObject;

enum LocationLevel: int
{
    case ZONA = 1;
    case PASILLO = 2;
    case ESTANTE = 3;
    case POSICION = 4;

    public function label(): string
    {
        return match ($this) {
            self::ZONA => 'Zona',
            self::PASILLO => 'Pasillo',
            self::ESTANTE => 'Estante',
            self::POSICION => 'Posición',
        };
    }

    public function expectedParentLevel(): ?self
    {
        return match ($this) {
            self::ZONA => null,
            self::PASILLO => self::ZONA,
            self::ESTANTE => self::PASILLO,
            self::POSICION => self::ESTANTE,
        };
    }

    public function isLeaf(): bool
    {
        return $this === self::POSICION;
    }

    public function childLevel(): ?self
    {
        return match ($this) {
            self::ZONA => self::PASILLO,
            self::PASILLO => self::ESTANTE,
            self::ESTANTE => self::POSICION,
            self::POSICION => null,
        };
    }
}
