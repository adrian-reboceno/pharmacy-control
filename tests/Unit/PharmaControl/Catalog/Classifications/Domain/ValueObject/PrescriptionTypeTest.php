<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Classifications/Domain/ValueObject/PrescriptionTypeTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Classifications\Domain\ValueObject;

use PharmaControl\Catalog\Classifications\Domain\ValueObject\PrescriptionType;
use PHPUnit\Framework\TestCase;

final class PrescriptionTypeTest extends TestCase
{
    public function test_has_three_valid_values(): void
    {
        $cases = PrescriptionType::cases();
        self::assertCount(3, $cases);
    }

    public function test_label_returns_human_readable_string(): void
    {
        self::assertSame('Con código de barras', PrescriptionType::CON_CODIGO_BARRAS->label());
        self::assertSame('Receta normal', PrescriptionType::NORMAL->label());
        self::assertSame('Sin receta', PrescriptionType::SIN_RECETA->label());
    }

    public function test_from_throws_value_error_for_invalid_string(): void
    {
        $this->expectException(\ValueError::class);

        PrescriptionType::from('RECETA_ELECTRONICA');
    }

    public function test_backed_values_match_expected_strings(): void
    {
        self::assertSame('CON_CODIGO_BARRAS', PrescriptionType::CON_CODIGO_BARRAS->value);
        self::assertSame('NORMAL', PrescriptionType::NORMAL->value);
        self::assertSame('SIN_RECETA', PrescriptionType::SIN_RECETA->value);
    }
}
