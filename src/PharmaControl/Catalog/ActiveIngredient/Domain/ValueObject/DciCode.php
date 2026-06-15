<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject;

final readonly class DciCode
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El código DCI no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 30) {
            throw new \InvalidArgumentException('El código DCI no puede exceder 30 caracteres.');
        }
        if (! preg_match('/^[\p{L}\p{N}\s\-()+]+$/u', $trimmed)) {
            throw new \InvalidArgumentException("Código DCI inválido: {$trimmed}");
        }
        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
