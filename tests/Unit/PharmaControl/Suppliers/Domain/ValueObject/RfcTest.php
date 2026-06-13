<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Domain\ValueObject;

use PharmaControl\Suppliers\Domain\Exception\InvalidRfcException;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;
use PHPUnit\Framework\TestCase;

final class RfcTest extends TestCase
{
    public function test_accepts_valid_moral_rfc_12_chars(): void
    {
        $rfc = new Rfc('ABC123456XYZ', SupplierType::MORAL);
        self::assertSame('ABC123456XYZ', $rfc->value);
    }

    public function test_accepts_valid_fisica_rfc_13_chars(): void
    {
        $rfc = new Rfc('ABCD123456XYZ', SupplierType::FISICA);
        self::assertSame('ABCD123456XYZ', $rfc->value);
    }

    public function test_normalizes_to_uppercase(): void
    {
        $rfc = new Rfc('abc123456xyz', SupplierType::MORAL);
        self::assertSame('ABC123456XYZ', $rfc->value);
    }

    public function test_trims_whitespace_before_validating(): void
    {
        $rfc = new Rfc('  ABC123456XYZ  ', SupplierType::MORAL);
        self::assertSame('ABC123456XYZ', $rfc->value);
    }

    public function test_throws_for_moral_rfc_with_wrong_length(): void
    {
        $this->expectException(InvalidRfcException::class);
        new Rfc('ABCD123456XYZ', SupplierType::MORAL); // 13 chars, expects 12
    }

    public function test_throws_for_fisica_rfc_with_wrong_length(): void
    {
        $this->expectException(InvalidRfcException::class);
        new Rfc('ABC123456XYZ', SupplierType::FISICA); // 12 chars, expects 13
    }

    public function test_throws_for_invalid_format_all_digits(): void
    {
        $this->expectException(InvalidRfcException::class);
        new Rfc('123456789012', SupplierType::MORAL);
    }

    public function test_accepts_rfc_starting_with_ampersand(): void
    {
        $rfc = new Rfc('&BC123456XYZ', SupplierType::MORAL);
        self::assertSame('&BC123456XYZ', $rfc->value);
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $a = new Rfc('ABC123456XYZ', SupplierType::MORAL);
        $b = new Rfc('ABC123456XYZ', SupplierType::MORAL);
        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $a = new Rfc('ABC123456XYZ', SupplierType::MORAL);
        $b = new Rfc('XYZ123456ABC', SupplierType::MORAL);
        self::assertFalse($a->equals($b));
    }
}
