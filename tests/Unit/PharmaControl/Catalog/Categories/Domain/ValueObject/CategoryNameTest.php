<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Categories/Domain/ValueObject/CategoryNameTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Categories\Domain\ValueObject;

use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PHPUnit\Framework\TestCase;

final class CategoryNameTest extends TestCase
{
    public function test_accepts_valid_name_up_to_120_chars(): void
    {
        $name = new CategoryName(str_repeat('a', 120));

        self::assertSame(str_repeat('a', 120), $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CategoryName('');
    }

    public function test_rejects_name_over_120_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CategoryName(str_repeat('a', 121));
    }

    public function test_trims_whitespace(): void
    {
        $name = new CategoryName('  Antibióticos  ');

        self::assertSame('Antibióticos', $name->value);
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new CategoryName('Antibióticos');
        $b = new CategoryName('antibióticos');

        self::assertTrue($a->equals($b));
    }
}
