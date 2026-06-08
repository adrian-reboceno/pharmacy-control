<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Domain/ValueObject/TotpCodeTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Domain\ValueObject;

use PharmaControl\Auth\Domain\ValueObject\TotpCode;
use PHPUnit\Framework\TestCase;

final class TotpCodeTest extends TestCase
{
    public function test_accepts_exactly_6_numeric_digits(): void
    {
        $code = new TotpCode('123456');

        self::assertSame('123456', $code->value);
    }

    public function test_rejects_5_digits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TotpCode('12345');
    }

    public function test_rejects_7_digits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TotpCode('1234567');
    }

    public function test_rejects_alphanumeric_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TotpCode('12345a');
    }
}
