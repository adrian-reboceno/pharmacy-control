<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\ValueObject;

final readonly class PresentationName
{
    public function __construct(public readonly string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre de la presentación no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 100) {
            throw new \InvalidArgumentException('El nombre no puede exceder 100 caracteres.');
        }
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
