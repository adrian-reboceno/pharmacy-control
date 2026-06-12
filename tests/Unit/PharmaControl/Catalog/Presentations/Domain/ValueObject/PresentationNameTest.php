<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Presentations\Domain\ValueObject;

use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PHPUnit\Framework\TestCase;

final class PresentationNameTest extends TestCase
{
    public function test_accepts_valid_name_up_to_100_chars(): void
    {
        $name = new PresentationName(str_repeat('a', 100));

        self::assertSame(str_repeat('a', 100), $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PresentationName('');
    }

    public function test_rejects_name_over_100_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PresentationName(str_repeat('a', 101));
    }

    public function test_trims_whitespace(): void
    {
        $name = new PresentationName('  Tableta  ');

        self::assertSame('  Tableta  ', $name->value);
    }

    public function test_rejects_whitespace_only(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PresentationName('   ');
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new PresentationName('Tableta');
        $b = new PresentationName('TABLETA');

        self::assertTrue($a->equals($b));
        self::assertTrue($b->equals($a));
    }

    public function test_equals_returns_false_for_different_names(): void
    {
        $a = new PresentationName('Tableta');
        $b = new PresentationName('Cápsula');

        self::assertFalse($a->equals($b));
    }
}
