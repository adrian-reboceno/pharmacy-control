<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject;

final readonly class UnitName
{
    public function __construct(public readonly string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre de la unidad no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 80) {
            throw new \InvalidArgumentException('El nombre no puede exceder 80 caracteres.');
        }
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
