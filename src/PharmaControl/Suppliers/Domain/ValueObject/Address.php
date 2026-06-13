<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\ValueObject;

final readonly class Address
{
    private const VALID_STATES = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
        'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima',
        'Durango', 'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo',
        'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca',
        'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa',
        'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán',
        'Zacatecas',
    ];

    public function __construct(
        public readonly string $street,
        public readonly string $extNumber,
        public readonly ?string $intNumber,
        public readonly string $neighborhood,
        public readonly string $municipality,
        public readonly string $state,
        public readonly string $postalCode,
        public readonly string $country = 'MX',
    ) {
        $this->validateRequired($street, 'calle', 150);
        $this->validateRequired($extNumber, 'número exterior', 20);
        if ($intNumber !== null) {
            $this->validateRequired($intNumber, 'número interior', 20);
        }
        $this->validateRequired($neighborhood, 'colonia', 100);
        $this->validateRequired($municipality, 'municipio', 100);

        if (! in_array($state, self::VALID_STATES, true)) {
            throw new \InvalidArgumentException("Estado inválido: {$state}");
        }

        if (! preg_match('/^\d{5}$/', $postalCode)) {
            throw new \InvalidArgumentException("Código postal inválido: {$postalCode}. Debe ser 5 dígitos.");
        }

        if (mb_strlen($country) !== 2) {
            throw new \InvalidArgumentException("País inválido: {$country}. Debe ser código ISO 3166-1 alpha-2.");
        }
    }

    private function validateRequired(string $value, string $field, int $maxLength): void
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException("El campo {$field} no puede estar vacío.");
        }
        if (mb_strlen($trimmed) > $maxLength) {
            throw new \InvalidArgumentException("El campo {$field} no puede exceder {$maxLength} caracteres.");
        }
    }

    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->extNumber === $other->extNumber
            && $this->intNumber === $other->intNumber
            && $this->neighborhood === $other->neighborhood
            && $this->municipality === $other->municipality
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }

    public function toFullString(): string
    {
        $line = "{$this->street} {$this->extNumber}";
        if ($this->intNumber !== null) {
            $line .= " Int. {$this->intNumber}";
        }
        $line .= ", {$this->neighborhood}, {$this->municipality}, {$this->state}, CP {$this->postalCode}, {$this->country}";

        return $line;
    }
}
