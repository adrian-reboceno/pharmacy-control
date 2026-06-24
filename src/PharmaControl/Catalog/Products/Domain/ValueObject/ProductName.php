<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\ValueObject;

final readonly class ProductName
{
    public function __construct(public readonly string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('El nombre del producto no puede estar vacío.');
        }
        if (mb_strlen($trimmed) > 200) {
            throw new \InvalidArgumentException('El nombre del producto no puede superar los 200 caracteres.');
        }
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
