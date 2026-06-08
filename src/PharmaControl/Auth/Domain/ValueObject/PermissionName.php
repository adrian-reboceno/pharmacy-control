<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/PermissionName.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

final readonly class PermissionName
{
    public function __construct(public readonly string $value)
    {
        if (! preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/', $value)) {
            throw new \InvalidArgumentException(
                "Nombre de permiso inválido (patrón esperado: {mod}.{ent}.{acc}): {$value}"
            );
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
