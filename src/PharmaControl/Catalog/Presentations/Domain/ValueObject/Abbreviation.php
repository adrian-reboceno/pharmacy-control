<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\ValueObject;

final readonly class Abbreviation
{
    public function __construct(public readonly string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('La abreviatura no puede estar vacía.');
        }
        if (mb_strlen($trimmed) > 20) {
            throw new \InvalidArgumentException('La abreviatura no puede exceder 20 caracteres.');
        }
        if (!preg_match('/^[\p{L}\p{N}.\-_]+$/u', $trimmed)) {
            throw new \InvalidArgumentException("Abreviatura inválida: {$trimmed}. Solo letras, números, puntos y guiones.");
        }
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
