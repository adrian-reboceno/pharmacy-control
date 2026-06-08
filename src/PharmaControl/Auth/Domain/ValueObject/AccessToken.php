<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/AccessToken.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

final readonly class AccessToken
{
    public function __construct(public readonly string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('El access token no puede estar vacío.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
