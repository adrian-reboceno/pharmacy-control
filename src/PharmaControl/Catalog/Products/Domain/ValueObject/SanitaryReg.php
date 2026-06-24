<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\ValueObject;

final readonly class SanitaryReg
{
    public function __construct(public readonly string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El registro sanitario no puede estar vacío.');
        }
        if (mb_strlen($trimmed) < 5 || mb_strlen($trimmed) > 50) {
            throw new \InvalidArgumentException("Registro sanitario inválido: {$value}. Debe tener entre 5 y 50 caracteres.");
        }
    }

    public function equals(self $other): bool
    {
        return mb_strtoupper($this->value) === mb_strtoupper($other->value);
    }
}
