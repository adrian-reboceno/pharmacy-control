<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject;

final readonly class RouteCode
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '') {
            throw new \InvalidArgumentException('El código de vía no puede estar vacío.');
        }
        if (mb_strlen($normalized) > 10) {
            throw new \InvalidArgumentException('El código no puede exceder 10 caracteres.');
        }
        if (!preg_match('/^[A-Z0-9]+$/', $normalized)) {
            throw new \InvalidArgumentException("Código inválido: {$value}. Solo letras mayúsculas y números.");
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
