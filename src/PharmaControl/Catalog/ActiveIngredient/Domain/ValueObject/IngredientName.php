<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject;

final readonly class IngredientName
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre del ingrediente activo no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 150) {
            throw new \InvalidArgumentException('El nombre no puede exceder 150 caracteres.');
        }
        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
