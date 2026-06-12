<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject;

final readonly class RouteName
{
    public function __construct(public readonly string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre de la vía de administración no puede estar vacío.');
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
