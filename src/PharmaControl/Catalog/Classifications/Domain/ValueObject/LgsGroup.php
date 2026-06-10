<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/ValueObject/LgsGroup.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\ValueObject;

enum LgsGroup: string
{
    case I = 'I';
    case II = 'II';
    case III = 'III';
    case IV_A = 'IV_A';
    case IV_B = 'IV_B';
    case V = 'V';
    case VI = 'VI';

    public function label(): string
    {
        return match ($this) {
            self::I => 'Grupo I — Estupefacientes',
            self::II => 'Grupo II — Psicotrópicos',
            self::III => 'Grupo III — Psicotrópicos',
            self::IV_A => 'Grupo IV-A — Antibióticos',
            self::IV_B => 'Grupo IV-B — Medicamentos con receta',
            self::V => 'Grupo V — OTC exclusivo farmacias',
            self::VI => 'Grupo VI — OTC libre acceso',
        };
    }

    public function isControlled(): bool
    {
        return in_array($this, [self::I, self::II, self::III, self::IV_A, self::IV_B], true);
    }
}
