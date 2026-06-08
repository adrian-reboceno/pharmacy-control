<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/Password.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

final readonly class Password
{
    public function __construct(public readonly string $value)
    {
        if (strlen($value) < 8) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
        }
        if (! preg_match('/[A-Z]/', $value)) {
            throw new \InvalidArgumentException('La contraseña debe contener al menos una letra mayúscula.');
        }
        if (! preg_match('/[a-z]/', $value)) {
            throw new \InvalidArgumentException('La contraseña debe contener al menos una letra minúscula.');
        }
        if (! preg_match('/[0-9]/', $value)) {
            throw new \InvalidArgumentException('La contraseña debe contener al menos un número.');
        }
        if (! preg_match('/[^A-Za-z0-9]/', $value)) {
            throw new \InvalidArgumentException('La contraseña debe contener al menos un carácter especial.');
        }
    }
}
