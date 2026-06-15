<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject;

use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PHPUnit\Framework\TestCase;

final class DciCodeTest extends TestCase
{
    public function test_accepts_simple_dci_code_amoxicillin(): void
    {
        $code = new DciCode('amoxicillin');
        self::assertSame('amoxicillin', $code->value);
    }

    public function test_accepts_dci_code_with_spaces_metformin_hydrochloride(): void
    {
        $code = new DciCode('metformin hydrochloride');
        self::assertSame('metformin hydrochloride', $code->value);
    }

    public function test_accepts_dci_code_with_parentheses(): void
    {
        $code = new DciCode('acetylsalicylic acid');
        self::assertSame('acetylsalicylic acid', $code->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        new DciCode('');
    }

    public function test_rejects_dci_code_over_30_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede exceder 30 caracteres');

        new DciCode(str_repeat('a', 31));
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new DciCode('Amoxicillin');
        $b = new DciCode('amoxicillin');

        self::assertTrue($a->equals($b));
        self::assertTrue($b->equals($a));
    }
}
