<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Domain\ValueObject;

use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    private function makeAddress(array $overrides = []): Address
    {
        return new Address(
            street: $overrides['street'] ?? 'Av. Reforma',
            extNumber: $overrides['extNumber'] ?? '123',
            intNumber: $overrides['intNumber'] ?? null,
            neighborhood: $overrides['neighborhood'] ?? 'Centro',
            municipality: $overrides['municipality'] ?? 'Puebla',
            state: $overrides['state'] ?? 'Puebla',
            postalCode: $overrides['postalCode'] ?? '72000',
            country: $overrides['country'] ?? 'MX',
        );
    }

    public function test_accepts_valid_address(): void
    {
        $address = $this->makeAddress();
        self::assertSame('Av. Reforma', $address->street);
        self::assertSame('MX', $address->country);
    }

    public function test_defaults_country_to_mx(): void
    {
        $address = new Address('Calle', '1', null, 'Col', 'Mun', 'Puebla', '72000');
        self::assertSame('MX', $address->country);
    }

    public function test_throws_for_invalid_state(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Estado inválido');

        $this->makeAddress(['state' => 'EstadoInventado']);
    }

    public function test_throws_for_postal_code_with_letters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Código postal inválido');

        $this->makeAddress(['postalCode' => '7200A']);
    }

    public function test_throws_for_postal_code_with_less_than_5_digits(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeAddress(['postalCode' => '7200']);
    }

    public function test_throws_for_postal_code_with_more_than_5_digits(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeAddress(['postalCode' => '720001']);
    }

    public function test_throws_for_country_not_2_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('País inválido');

        $this->makeAddress(['country' => 'MEX']);
    }

    public function test_throws_for_empty_street(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeAddress(['street' => '']);
    }

    public function test_throws_for_street_exceeding_max_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeAddress(['street' => str_repeat('a', 151)]);
    }

    public function test_to_full_string_without_int_number(): void
    {
        $address = $this->makeAddress();
        self::assertSame(
            'Av. Reforma 123, Centro, Puebla, Puebla, CP 72000, MX',
            $address->toFullString()
        );
    }

    public function test_to_full_string_with_int_number(): void
    {
        $address = $this->makeAddress(['intNumber' => 'A']);
        self::assertSame(
            'Av. Reforma 123 Int. A, Centro, Puebla, Puebla, CP 72000, MX',
            $address->toFullString()
        );
    }

    public function test_equals_returns_true_for_identical_addresses(): void
    {
        $a = $this->makeAddress();
        $b = $this->makeAddress();
        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_when_street_differs(): void
    {
        $a = $this->makeAddress(['street' => 'Av. Reforma']);
        $b = $this->makeAddress(['street' => 'Calle 5']);
        self::assertFalse($a->equals($b));
    }

    public function test_accepts_all_32_mexican_states(): void
    {
        $validStates = [
            'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
            'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima',
            'Durango', 'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo',
            'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca',
            'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa',
            'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán',
            'Zacatecas',
        ];

        foreach ($validStates as $state) {
            $address = $this->makeAddress(['state' => $state]);
            self::assertSame($state, $address->state);
        }

        self::assertCount(32, $validStates);
    }
}
