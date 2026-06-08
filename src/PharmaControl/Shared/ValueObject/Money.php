<?php

// ── ARCHIVO: src/PharmaControl/Shared/ValueObject/Money.php ──
declare(strict_types=1);

namespace PharmaControl\Shared\ValueObject;

final readonly class Money
{
    public function __construct(
        public readonly int $amountCents,
        public readonly string $currency,
    ) {
        if ($this->amountCents < 0) {
            throw new \InvalidArgumentException("El monto no puede ser negativo: {$this->amountCents}");
        }
        if (! preg_match('/^[A-Z]{3}$/', $this->currency)) {
            throw new \InvalidArgumentException("Código de moneda ISO 4217 inválido: {$this->currency}");
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                "No se pueden sumar monedas distintas: {$this->currency} y {$other->currency}"
            );
        }

        return new self($this->amountCents + $other->amountCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                "No se pueden restar monedas distintas: {$this->currency} y {$other->currency}"
            );
        }
        $result = $this->amountCents - $other->amountCents;
        if ($result < 0) {
            throw new \InvalidArgumentException('El resultado del monto sería negativo.');
        }

        return new self($result, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amountCents === $other->amountCents && $this->currency === $other->currency;
    }

    public function toFloat(): float
    {
        return $this->amountCents / 100;
    }

    public function __toString(): string
    {
        return number_format($this->toFloat(), 2).' '.$this->currency;
    }
}
