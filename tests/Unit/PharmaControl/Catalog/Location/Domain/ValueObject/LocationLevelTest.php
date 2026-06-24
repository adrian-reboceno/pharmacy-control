<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Location\Domain\ValueObject;

use PharmaControl\Catalog\Location\Domain\ValueObject\LocationLevel;
use PHPUnit\Framework\TestCase;

final class LocationLevelTest extends TestCase
{
    public function test_values_match_expected_integers(): void
    {
        self::assertSame(1, LocationLevel::ZONA->value);
        self::assertSame(2, LocationLevel::PASILLO->value);
        self::assertSame(3, LocationLevel::ESTANTE->value);
        self::assertSame(4, LocationLevel::POSICION->value);
    }

    public function test_labels_are_correct(): void
    {
        self::assertSame('Zona', LocationLevel::ZONA->label());
        self::assertSame('Pasillo', LocationLevel::PASILLO->label());
        self::assertSame('Estante', LocationLevel::ESTANTE->label());
        self::assertSame('Posición', LocationLevel::POSICION->label());
    }

    public function test_expected_parent_level_returns_null_for_zona(): void
    {
        self::assertNull(LocationLevel::ZONA->expectedParentLevel());
    }

    public function test_expected_parent_level_for_pasillo_is_zona(): void
    {
        self::assertSame(LocationLevel::ZONA, LocationLevel::PASILLO->expectedParentLevel());
    }

    public function test_expected_parent_level_for_estante_is_pasillo(): void
    {
        self::assertSame(LocationLevel::PASILLO, LocationLevel::ESTANTE->expectedParentLevel());
    }

    public function test_expected_parent_level_for_posicion_is_estante(): void
    {
        self::assertSame(LocationLevel::ESTANTE, LocationLevel::POSICION->expectedParentLevel());
    }

    public function test_child_level_for_zona_is_pasillo(): void
    {
        self::assertSame(LocationLevel::PASILLO, LocationLevel::ZONA->childLevel());
    }

    public function test_child_level_for_pasillo_is_estante(): void
    {
        self::assertSame(LocationLevel::ESTANTE, LocationLevel::PASILLO->childLevel());
    }

    public function test_child_level_for_estante_is_posicion(): void
    {
        self::assertSame(LocationLevel::POSICION, LocationLevel::ESTANTE->childLevel());
    }

    public function test_child_level_for_posicion_is_null(): void
    {
        self::assertNull(LocationLevel::POSICION->childLevel());
    }

    public function test_only_posicion_is_leaf(): void
    {
        self::assertFalse(LocationLevel::ZONA->isLeaf());
        self::assertFalse(LocationLevel::PASILLO->isLeaf());
        self::assertFalse(LocationLevel::ESTANTE->isLeaf());
        self::assertTrue(LocationLevel::POSICION->isLeaf());
    }

    public function test_from_int_returns_correct_case(): void
    {
        self::assertSame(LocationLevel::ZONA, LocationLevel::from(1));
        self::assertSame(LocationLevel::PASILLO, LocationLevel::from(2));
        self::assertSame(LocationLevel::ESTANTE, LocationLevel::from(3));
        self::assertSame(LocationLevel::POSICION, LocationLevel::from(4));
    }

    public function test_from_invalid_int_throws_error(): void
    {
        $this->expectException(\ValueError::class);
        LocationLevel::from(5);
    }
}
