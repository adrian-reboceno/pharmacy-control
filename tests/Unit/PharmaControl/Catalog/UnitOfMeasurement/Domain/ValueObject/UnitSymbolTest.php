<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject;

use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PHPUnit\Framework\TestCase;

final class UnitSymbolTest extends TestCase
{
    public function test_accepts_valid_symbol_mg(): void
    {
        $symbol = new UnitSymbol('mg');

        self::assertSame('mg', $symbol->value);
    }

    public function test_accepts_valid_symbol_ui_ml(): void
    {
        $symbol = new UnitSymbol('UI/ml');

        self::assertSame('UI/ml', $symbol->value);
    }

    public function test_accepts_valid_symbol_mcg(): void
    {
        $symbol = new UnitSymbol('mcg');

        self::assertSame('mcg', $symbol->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UnitSymbol('');
    }

    public function test_rejects_symbol_over_20_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UnitSymbol(str_repeat('a', 21));
    }

    public function test_equals_is_case_sensitive_mg_differs_from_mg_uppercase(): void
    {
        $lower = new UnitSymbol('mg');
        $upper = new UnitSymbol('MG');

        self::assertFalse($lower->equals($upper));
        self::assertTrue($lower->equals(new UnitSymbol('mg')));
    }
}
