<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\ValueObject;

final readonly class StatusCode
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '') {
            throw new \InvalidArgumentException('El código de estado no puede estar vacío.');
        }
        if (mb_strlen($normalized) > 20) {
            throw new \InvalidArgumentException('El código no puede exceder 20 caracteres.');
        }
        if (! preg_match('/^[A-Z0-9_]+$/', $normalized)) {
            throw new \InvalidArgumentException("Código inválido: {$value}. Solo letras mayúsculas, números y guion bajo.");
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
