<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject;

final readonly class CasNumber
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El número CAS no puede estar vacío.');
        }
        if (! preg_match('/^\d{2,7}-\d{2}-\d$/', $trimmed)) {
            throw new \InvalidArgumentException(
                "Formato CAS inválido: {$trimmed}. Formato esperado: NNNNNN-NN-N (ej: 26787-78-0)"
            );
        }
        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
