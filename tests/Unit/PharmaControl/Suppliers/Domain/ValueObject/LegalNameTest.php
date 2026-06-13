<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Domain\ValueObject;

use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PHPUnit\Framework\TestCase;

final class LegalNameTest extends TestCase
{
    public function test_accepts_valid_name(): void
    {
        $name = new LegalName('Distribuidora Farmacéutica del Centro S.A. de C.V.');
        self::assertSame('Distribuidora Farmacéutica del Centro S.A. de C.V.', $name->value);
    }

    public function test_accepts_name_up_to_200_chars(): void
    {
        $name = new LegalName(str_repeat('a', 200));
        self::assertSame(str_repeat('a', 200), $name->value);
    }

    public function test_trims_whitespace(): void
    {
        $name = new LegalName('  Farmacia Central  ');
        self::assertSame('Farmacia Central', $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede estar vacía');

        new LegalName('');
    }

    public function test_rejects_whitespace_only_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LegalName('   ');
    }

    public function test_rejects_name_exceeding_200_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('200');

        new LegalName(str_repeat('a', 201));
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new LegalName('Farmacia Central');
        $b = new LegalName('FARMACIA CENTRAL');
        self::assertTrue($a->equals($b));
        self::assertTrue($b->equals($a));
    }

    public function test_equals_returns_false_for_different_names(): void
    {
        $a = new LegalName('Farmacia Central');
        $b = new LegalName('Farmacia Norte');
        self::assertFalse($a->equals($b));
    }
}
