<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/RefreshToken.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

final readonly class RefreshToken
{
    public function __construct(public readonly string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('El refresh token no puede estar vacío.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
