<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject;

use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PHPUnit\Framework\TestCase;

final class IngredientNameTest extends TestCase
{
    public function test_accepts_valid_name_up_to_150_chars(): void
    {
        $name = new IngredientName(str_repeat('a', 150));
        self::assertSame(str_repeat('a', 150), $name->value);
    }

    public function test_accepts_composite_name_with_plus_sign(): void
    {
        $name = new IngredientName('Amoxicilina + Ácido Clavulánico');
        self::assertSame('Amoxicilina + Ácido Clavulánico', $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        new IngredientName('');
    }

    public function test_rejects_name_over_150_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede exceder 150 caracteres');

        new IngredientName(str_repeat('a', 151));
    }

    public function test_trims_whitespace(): void
    {
        $name = new IngredientName('  Amoxicilina  ');
        self::assertSame('Amoxicilina', $name->value);
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new IngredientName('Amoxicilina');
        $b = new IngredientName('amoxicilina');

        self::assertTrue($a->equals($b));
        self::assertTrue($b->equals($a));
    }
}
