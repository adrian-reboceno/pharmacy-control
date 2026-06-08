<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/TotpCode.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

final readonly class TotpCode
{
    public function __construct(public readonly string $value)
    {
        if (! preg_match('/^\d{6}$/', $value)) {
            throw new \InvalidArgumentException(
                "El código TOTP debe tener exactamente 6 dígitos numéricos. Recibido: {$value}"
            );
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
