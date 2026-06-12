<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject;

use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PHPUnit\Framework\TestCase;

final class RouteCodeTest extends TestCase
{
    public function test_accepts_valid_code_vo(): void
    {
        $code = new RouteCode('VO');

        self::assertSame('VO', $code->value);
    }

    public function test_normalizes_lowercase_code_to_uppercase(): void
    {
        $code = new RouteCode('vo');

        self::assertSame('VO', $code->value);
    }

    public function test_accepts_alphanumeric_code_td2(): void
    {
        $code = new RouteCode('TD2');

        self::assertSame('TD2', $code->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RouteCode('');
    }

    public function test_rejects_code_over_10_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RouteCode('ABCDEFGHIJK');
    }

    public function test_rejects_code_with_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RouteCode('V O');
    }

    public function test_rejects_code_with_special_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RouteCode('V.O');
    }

    public function test_equals_is_case_insensitive_vo_equals_vo_after_normalization(): void
    {
        $upper = new RouteCode('VO');
        $lower = new RouteCode('vo');

        self::assertTrue($upper->equals($lower));
        self::assertTrue($lower->equals($upper));
    }
}
