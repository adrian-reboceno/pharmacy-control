<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject;

use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PHPUnit\Framework\TestCase;

final class RouteNameTest extends TestCase
{
    public function test_accepts_valid_name_up_to_100_chars(): void
    {
        $name = new RouteName(str_repeat('a', 100));

        self::assertSame(str_repeat('a', 100), $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RouteName('');
    }

    public function test_rejects_name_over_100_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RouteName(str_repeat('a', 101));
    }

    public function test_trims_whitespace(): void
    {
        $name = new RouteName('  Oral  ');

        self::assertSame('  Oral  ', $name->value);
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new RouteName('Oral');
        $b = new RouteName('ORAL');

        self::assertTrue($a->equals($b));
        self::assertTrue($b->equals($a));
    }
}
