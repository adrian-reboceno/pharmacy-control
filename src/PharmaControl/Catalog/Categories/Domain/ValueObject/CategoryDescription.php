<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/ValueObject/CategoryDescription.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\ValueObject;

final readonly class CategoryDescription
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('La descripción no puede estar vacía.');
        }
        if (mb_strlen($trimmed) > 500) {
            throw new \InvalidArgumentException('La descripción no puede exceder 500 caracteres.');
        }
        $this->value = $trimmed;
    }
}
