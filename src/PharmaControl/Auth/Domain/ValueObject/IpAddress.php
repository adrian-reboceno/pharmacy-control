<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/ValueObject/IpAddress.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\ValueObject;

final readonly class IpAddress
{
    public function __construct(public readonly string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException("Dirección IP inválida: {$value}");
        }
    }

    public function isV6(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
