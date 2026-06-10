<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/ValueObject/ClassificationName.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\ValueObject;

final readonly class ClassificationName
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre de clasificación no puede estar vacío.');
        }

        if (mb_strlen($trimmed) > 100) {
            throw new \InvalidArgumentException('El nombre no puede exceder 100 caracteres.');
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
