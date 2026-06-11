<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject;

use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PHPUnit\Framework\TestCase;

final class UnitNameTest extends TestCase
{
    public function test_accepts_valid_name_up_to_80_chars(): void
    {
        $name = new UnitName(str_repeat('a', 80));

        self::assertSame(str_repeat('a', 80), $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UnitName('');
    }

    public function test_rejects_name_over_80_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UnitName(str_repeat('a', 81));
    }

    public function test_trims_whitespace(): void
    {
        $name = new UnitName('  Miligramo  ');

        self::assertSame('  Miligramo  ', $name->value);
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new UnitName('Miligramo');
        $b = new UnitName('MILIGRAMO');

        self::assertTrue($a->equals($b));
        self::assertTrue($b->equals($a));
    }
}
