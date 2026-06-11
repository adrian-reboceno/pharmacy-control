<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject;

use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;
use PHPUnit\Framework\TestCase;

final class UnitTypeTest extends TestCase
{
    public function test_has_two_valid_values_quantity_and_concentration(): void
    {
        self::assertSame('QUANTITY', UnitType::QUANTITY->value);
        self::assertSame('CONCENTRATION', UnitType::CONCENTRATION->value);
    }

    public function test_label_returns_human_readable_string(): void
    {
        self::assertSame('Cantidad / Presentación', UnitType::QUANTITY->label());
        self::assertSame('Concentración / Dosis', UnitType::CONCENTRATION->label());
    }

    public function test_from_throws_value_error_for_invalid_string(): void
    {
        $this->expectException(\ValueError::class);

        UnitType::from('INVALID');
    }
}
