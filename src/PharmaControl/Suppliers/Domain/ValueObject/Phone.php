<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\ValueObject;

final readonly class Phone
{
    public string $value;

    public function __construct(string $value)
    {
        $digitsOnly = preg_replace('/\D/', '', $value);

        if (mb_strlen($digitsOnly) !== 10) {
            throw new \InvalidArgumentException("Teléfono inválido: {$value}. Debe tener 10 dígitos.");
        }

        $this->value = $digitsOnly;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
