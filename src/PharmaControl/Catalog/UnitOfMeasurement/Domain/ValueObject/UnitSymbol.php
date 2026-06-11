<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject;

final readonly class UnitSymbol
{
    public function __construct(public readonly string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El símbolo de la unidad no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 20) {
            throw new \InvalidArgumentException('El símbolo no puede exceder 20 caracteres.');
        }
        if (!preg_match('/^[\p{L}\p{N}\/%.·²³µ\s]+$/u', $trimmed)) {
            throw new \InvalidArgumentException("Símbolo inválido: {$trimmed}");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
