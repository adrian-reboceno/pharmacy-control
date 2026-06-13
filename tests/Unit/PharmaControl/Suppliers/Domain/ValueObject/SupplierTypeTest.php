<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Domain\ValueObject;

use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;
use PHPUnit\Framework\TestCase;

final class SupplierTypeTest extends TestCase
{
    public function test_moral_rfc_length_is_12(): void
    {
        self::assertSame(12, SupplierType::MORAL->rfcLength());
    }

    public function test_fisica_rfc_length_is_13(): void
    {
        self::assertSame(13, SupplierType::FISICA->rfcLength());
    }

    public function test_moral_label(): void
    {
        self::assertSame('Persona Moral', SupplierType::MORAL->label());
    }

    public function test_fisica_label(): void
    {
        self::assertSame('Persona Física', SupplierType::FISICA->label());
    }

    public function test_from_valid_string_moral(): void
    {
        self::assertSame(SupplierType::MORAL, SupplierType::from('MORAL'));
    }

    public function test_from_valid_string_fisica(): void
    {
        self::assertSame(SupplierType::FISICA, SupplierType::from('FISICA'));
    }

    public function test_from_invalid_string_throws_value_error(): void
    {
        $this->expectException(\ValueError::class);
        SupplierType::from('INVALID');
    }
}
