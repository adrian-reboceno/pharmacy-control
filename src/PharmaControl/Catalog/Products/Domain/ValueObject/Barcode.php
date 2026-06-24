<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\ValueObject;

use PharmaControl\Catalog\Products\Domain\Exception\InvalidBarcodeException;

final readonly class Barcode
{
    public function __construct(public readonly string $value)
    {
        if (! preg_match('/^\d{13}$/', $value)) {
            throw new InvalidBarcodeException("EAN-13 inválido: {$value}. Debe tener exactamente 13 dígitos.");
        }
        if (! $this->isValidCheckDigit($value)) {
            throw new InvalidBarcodeException("EAN-13 inválido: {$value}. El dígito verificador no es correcto.");
        }
    }

    private function isValidCheckDigit(string $barcode): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $barcode[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int) $barcode[12];
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
