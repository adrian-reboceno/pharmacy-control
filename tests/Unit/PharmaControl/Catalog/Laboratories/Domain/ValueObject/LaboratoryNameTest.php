<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Laboratories/Domain/ValueObject/LaboratoryNameTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Laboratories\Domain\ValueObject;

use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PHPUnit\Framework\TestCase;

final class LaboratoryNameTest extends TestCase
{
    public function test_accepts_valid_name_up_to_120_chars(): void
    {
        $name = new LaboratoryName(str_repeat('a', 120));
        self::assertSame(str_repeat('a', 120), $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        new LaboratoryName('');
    }

    public function test_rejects_string_exceeding_120_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede exceder 120 caracteres');

        new LaboratoryName(str_repeat('a', 121));
    }

    public function test_trims_whitespace(): void
    {
        $name = new LaboratoryName('  Bayer  ');
        self::assertSame('Bayer', $name->value);
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new LaboratoryName('Bayer');
        $b = new LaboratoryName('bayer');

        self::assertTrue($a->equals($b));
        self::assertTrue($b->equals($a));
    }
}
