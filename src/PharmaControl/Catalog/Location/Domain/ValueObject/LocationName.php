<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\ValueObject;

final readonly class LocationName
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre de la ubicación no puede estar vacío.');
        }

        if (mb_strlen($trimmed) > 100) {
            throw new \InvalidArgumentException('El nombre de la ubicación no puede exceder 100 caracteres.');
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
