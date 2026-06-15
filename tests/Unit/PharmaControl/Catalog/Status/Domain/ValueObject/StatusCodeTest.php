<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Status\Domain\ValueObject;

use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PHPUnit\Framework\TestCase;

final class StatusCodeTest extends TestCase
{
    public function test_accepts_valid_code_activo(): void
    {
        $code = new StatusCode('ACTIVO');
        $this->assertSame('ACTIVO', $code->value);
    }

    public function test_normalizes_lowercase_code_to_uppercase(): void
    {
        $code = new StatusCode('activo');
        $this->assertSame('ACTIVO', $code->value);
    }

    public function test_accepts_code_with_underscore_en_revision(): void
    {
        $code = new StatusCode('EN_REVISION');
        $this->assertSame('EN_REVISION', $code->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StatusCode('');
    }

    public function test_rejects_code_over_20_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StatusCode(str_repeat('A', 21));
    }

    public function test_rejects_code_with_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StatusCode('EN REVISION');
    }

    public function test_rejects_code_with_special_characters_other_than_underscore(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StatusCode('EN-REVISION');
    }

    public function test_equals_is_case_insensitive_activo_equals_activo_after_normalization(): void
    {
        $a = new StatusCode('activo');
        $b = new StatusCode('ACTIVO');

        $this->assertTrue($a->equals($b));
    }
}
