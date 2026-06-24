<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Location\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Location\Domain\Event\LocationCreated;
use PharmaControl\Catalog\Location\Domain\Event\LocationDeactivated;
use PharmaControl\Catalog\Location\Domain\Event\LocationUpdated;
use PharmaControl\Catalog\Location\Domain\Model\Location;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationLevel;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    private function makeZona(): Location
    {
        return Location::create(
            LocationId::generate(),
            LocationLevel::ZONA,
            null,
            new LocationName('OTC General'),
            'Medicamentos sin receta',
            UserId::generate(),
        );
    }

    private function makePosicion(?LocationId $parentId = null): Location
    {
        return Location::create(
            LocationId::generate(),
            LocationLevel::POSICION,
            $parentId ?? LocationId::generate(),
            new LocationName('Pos 1'),
            null,
            UserId::generate(),
        );
    }

    public function test_create_emits_location_created_event(): void
    {
        $location = $this->makeZona();

        $events = $location->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(LocationCreated::class, $events[0]);
    }

    public function test_create_zona_has_null_parent_and_level_1(): void
    {
        $location = $this->makeZona();

        self::assertNull($location->getParentId());
        self::assertSame(LocationLevel::ZONA, $location->getLevel());
        self::assertSame(1, $location->getLevel()->value);
    }

    public function test_create_posicion_is_leaf(): void
    {
        $location = $this->makePosicion();

        self::assertTrue($location->isLeaf());
        self::assertSame(LocationLevel::POSICION, $location->getLevel());
    }

    public function test_zona_is_not_leaf(): void
    {
        $location = $this->makeZona();

        self::assertFalse($location->isLeaf());
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $location = Location::reconstitute(
            id: LocationId::generate(),
            level: LocationLevel::ESTANTE,
            parentId: LocationId::generate(),
            name: new LocationName('Estante 1'),
            description: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        self::assertEmpty($location->releaseEvents());
    }

    public function test_update_changes_name_and_description_and_emits_event(): void
    {
        $location = $this->makeZona();
        $location->releaseEvents();

        $location->update(new LocationName('OTC Actualizado'), 'Nueva descripción');

        self::assertSame('OTC Actualizado', $location->getName()->value);
        self::assertSame('Nueva descripción', $location->getDescription());

        $events = $location->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(LocationUpdated::class, $events[0]);
    }

    public function test_update_sets_updated_at_to_current_time(): void
    {
        $location = $this->makeZona();
        $before = $location->getUpdatedAt();

        $location->update(new LocationName('Nuevo nombre'), null);

        self::assertGreaterThanOrEqual($before, $location->getUpdatedAt());
    }

    public function test_deactivate_sets_is_active_false_and_emits_event(): void
    {
        $location = $this->makeZona();
        $location->releaseEvents();

        $location->deactivate();

        self::assertFalse($location->isActive());

        $events = $location->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(LocationDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_when_already_inactive(): void
    {
        $location = $this->makeZona();
        $location->deactivate();
        $location->releaseEvents();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('ya está inactiva');

        $location->deactivate();
    }

    public function test_release_events_clears_internal_list(): void
    {
        $location = $this->makeZona();

        $first = $location->releaseEvents();
        $second = $location->releaseEvents();

        self::assertCount(1, $first);
        self::assertEmpty($second);
    }

    public function test_is_active_true_on_creation(): void
    {
        $location = $this->makeZona();

        self::assertTrue($location->isActive());
    }
}
