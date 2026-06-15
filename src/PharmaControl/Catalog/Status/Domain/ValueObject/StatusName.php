<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\ValueObject;

final readonly class StatusName
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre del estado no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 50) {
            throw new \InvalidArgumentException('El nombre no puede exceder 50 caracteres.');
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
