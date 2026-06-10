<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Laboratories/Domain/ValueObject/CountryCodeTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Laboratories\Domain\ValueObject;

use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PHPUnit\Framework\TestCase;

final class CountryCodeTest extends TestCase
{
    public function test_accepts_valid_iso_3166_1_alpha2_code_mx(): void
    {
        $code = new CountryCode('MX');
        self::assertSame('MX', $code->value);
    }

    public function test_accepts_valid_iso_3166_1_alpha2_code_us(): void
    {
        $code = new CountryCode('US');
        self::assertSame('US', $code->value);
    }

    public function test_normalizes_to_uppercase(): void
    {
        $code = new CountryCode('de');
        self::assertSame('DE', $code->value);
    }

    public function test_rejects_unknown_code_xx(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Código de país inválido: XX');

        new CountryCode('XX');
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CountryCode('');
    }

    public function test_rejects_code_longer_than_2_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CountryCode('MEX');
    }
}
