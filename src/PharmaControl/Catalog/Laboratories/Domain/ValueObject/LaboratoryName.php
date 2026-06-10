<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/ValueObject/LaboratoryName.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\ValueObject;

final readonly class LaboratoryName
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre del laboratorio no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 120) {
            throw new \InvalidArgumentException('El nombre del laboratorio no puede exceder 120 caracteres.');
        }
        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
