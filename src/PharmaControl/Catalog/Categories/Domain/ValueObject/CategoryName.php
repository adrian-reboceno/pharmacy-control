<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/ValueObject/CategoryName.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\ValueObject;

final readonly class CategoryName
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre de categoría no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 120) {
            throw new \InvalidArgumentException('El nombre no puede exceder 120 caracteres.');
        }
        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
