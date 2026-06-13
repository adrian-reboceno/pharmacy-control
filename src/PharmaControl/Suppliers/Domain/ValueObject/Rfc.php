<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\ValueObject;

use PharmaControl\Suppliers\Domain\Exception\InvalidRfcException;

final readonly class Rfc
{
    public string $value;

    public function __construct(string $value, SupplierType $type)
    {
        $normalized = strtoupper(trim($value));
        $expectedLength = $type->rfcLength();

        if (mb_strlen($normalized) !== $expectedLength) {
            throw new InvalidRfcException(
                "El RFC debe tener {$expectedLength} caracteres para {$type->label()}: {$value}"
            );
        }

        $letterCount = $type === SupplierType::MORAL ? 3 : 4;
        $pattern = '/^[A-ZÑ&]{' . $letterCount . '}\d{6}[A-Z0-9]{3}$/';

        if (! preg_match($pattern, $normalized)) {
            throw new InvalidRfcException("Formato de RFC inválido para {$type->label()}: {$value}");
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
