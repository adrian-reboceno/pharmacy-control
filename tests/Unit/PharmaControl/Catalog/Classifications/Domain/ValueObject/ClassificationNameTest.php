<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Classifications/Domain/ValueObject/ClassificationNameTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Classifications\Domain\ValueObject;

use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PHPUnit\Framework\TestCase;

final class ClassificationNameTest extends TestCase
{
    public function test_accepts_valid_name(): void
    {
        $name = new ClassificationName('Estupefacientes');

        self::assertSame('Estupefacientes', $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        new ClassificationName('');
    }

    public function test_rejects_whitespace_only_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClassificationName('   ');
    }

    public function test_rejects_name_over_100_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('100 caracteres');

        new ClassificationName(str_repeat('A', 101));
    }

    public function test_accepts_name_of_exactly_100_chars(): void
    {
        $name = new ClassificationName(str_repeat('A', 100));

        self::assertSame(100, mb_strlen($name->value));
    }

    public function test_trims_whitespace(): void
    {
        $name = new ClassificationName('  Estupefacientes  ');

        self::assertSame('Estupefacientes', $name->value);
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new ClassificationName('Estupefacientes');
        $b = new ClassificationName('ESTUPEFACIENTES');

        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_names(): void
    {
        $a = new ClassificationName('Estupefacientes');
        $b = new ClassificationName('Psicotrópicos');

        self::assertFalse($a->equals($b));
    }
}
