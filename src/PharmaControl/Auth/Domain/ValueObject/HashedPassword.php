<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/HashedPassword.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

final readonly class HashedPassword
{
    public function __construct(public readonly string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('El hash de contraseña no puede estar vacío.');
        }
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->value);
    }
}
