<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\ValueObject;

final readonly class LegalName
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('La razón social no puede estar vacía.');
        }
        if (mb_strlen($trimmed) > 200) {
            throw new \InvalidArgumentException('La razón social no puede exceder 200 caracteres.');
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
