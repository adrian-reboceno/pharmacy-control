<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Presentations\Domain\ValueObject;

use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PHPUnit\Framework\TestCase;

final class AbbreviationTest extends TestCase
{
    public function test_accepts_valid_abbreviation_tab(): void
    {
        $abbr = new Abbreviation('Tab');

        self::assertSame('Tab', $abbr->value);
    }

    public function test_accepts_abbreviation_with_dot_sol_iny(): void
    {
        $abbr = new Abbreviation('Sol.Iny');

        self::assertSame('Sol.Iny', $abbr->value);
    }

    public function test_accepts_abbreviation_with_hyphen(): void
    {
        $abbr = new Abbreviation('I-V');

        self::assertSame('I-V', $abbr->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Abbreviation('');
    }

    public function test_rejects_abbreviation_over_20_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Abbreviation(str_repeat('a', 21));
    }

    public function test_rejects_abbreviation_with_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Abbreviation('Sol Iny');
    }

    public function test_rejects_abbreviation_with_special_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Abbreviation('Tab@');
    }

    public function test_equals_is_case_insensitive_tab_equals_tab_uppercase(): void
    {
        $a = new Abbreviation('Tab');
        $b = new Abbreviation('TAB');

        self::assertTrue($a->equals($b));
        self::assertTrue($b->equals($a));
    }

    public function test_equals_returns_false_for_different_abbreviations(): void
    {
        $a = new Abbreviation('Tab');
        $b = new Abbreviation('Cap');

        self::assertFalse($a->equals($b));
    }
}
