<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject;

use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PHPUnit\Framework\TestCase;

final class CasNumberTest extends TestCase
{
    public function test_accepts_valid_cas_number_26787_78_0(): void
    {
        $cas = new CasNumber('26787-78-0');
        self::assertSame('26787-78-0', $cas->value);
    }

    public function test_accepts_valid_cas_number_103_90_2(): void
    {
        $cas = new CasNumber('103-90-2');
        self::assertSame('103-90-2', $cas->value);
    }

    public function test_accepts_valid_cas_number_114798_26_4(): void
    {
        $cas = new CasNumber('114798-26-4');
        self::assertSame('114798-26-4', $cas->value);
    }

    public function test_rejects_cas_number_without_dashes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formato CAS inválido');

        new CasNumber('2678778');
    }

    public function test_rejects_cas_number_with_wrong_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formato CAS inválido');

        new CasNumber('26787-7-0');
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        new CasNumber('');
    }

    public function test_equals_compares_exact_string(): void
    {
        $a = new CasNumber('26787-78-0');
        $b = new CasNumber('26787-78-0');

        self::assertTrue($a->equals($b));
    }
}
