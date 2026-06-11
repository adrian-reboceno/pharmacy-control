<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\UnitOfMeasurement\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitCreated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitDeactivated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitUpdated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;
use PHPUnit\Framework\TestCase;

final class UnitOfMeasurementTest extends TestCase
{
    private function makeUnit(
        string $name   = 'Miligramo',
        string $symbol = 'mg',
        string $type   = 'CONCENTRATION',
    ): UnitOfMeasurement {
        return UnitOfMeasurement::create(
            UnitId::generate(),
            new UnitName($name),
            new UnitSymbol($symbol),
            UnitType::from($type),
            UserId::generate(),
        );
    }

    public function test_create_emits_unit_created_event(): void
    {
        $unit   = $this->makeUnit();
        $events = $unit->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UnitCreated::class, $events[0]);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $unit = UnitOfMeasurement::reconstitute(
            id:        UnitId::generate(),
            name:      new UnitName('Gramo'),
            symbol:    new UnitSymbol('g'),
            type:      UnitType::CONCENTRATION,
            isActive:  true,
            createdBy: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        self::assertSame([], $unit->releaseEvents());
    }

    public function test_update_changes_name_symbol_and_type_and_emits_unit_updated(): void
    {
        $unit = $this->makeUnit('Miligramo', 'mg', 'CONCENTRATION');
        $unit->releaseEvents();

        $unit->update(new UnitName('Pieza'), new UnitSymbol('pza'), UnitType::QUANTITY);
        $events = $unit->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UnitUpdated::class, $events[0]);
        self::assertSame('Pieza', $unit->getName()->value);
        self::assertSame('pza', $unit->getSymbol()->value);
        self::assertSame(UnitType::QUANTITY, $unit->getType());
    }

    public function test_update_sets_updated_at_to_current_time(): void
    {
        $before = new \DateTimeImmutable();
        $unit   = $this->makeUnit();
        $unit->releaseEvents();

        $unit->update(new UnitName('Gramo'), new UnitSymbol('g'), UnitType::CONCENTRATION);

        self::assertGreaterThanOrEqual($before, $unit->getUpdatedAt());
    }

    public function test_deactivate_sets_is_active_false_and_emits_unit_deactivated(): void
    {
        $unit = $this->makeUnit();
        $unit->releaseEvents();

        $unit->deactivate();
        $events = $unit->releaseEvents();

        self::assertFalse($unit->isActive());
        self::assertCount(1, $events);
        self::assertInstanceOf(UnitDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $unit = $this->makeUnit();
        $unit->releaseEvents();
        $unit->deactivate();
        $unit->releaseEvents();

        $this->expectException(\DomainException::class);

        $unit->deactivate();
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $unit = $this->makeUnit();

        $first  = $unit->releaseEvents();
        $second = $unit->releaseEvents();

        self::assertCount(1, $first);
        self::assertCount(0, $second);
    }
}
