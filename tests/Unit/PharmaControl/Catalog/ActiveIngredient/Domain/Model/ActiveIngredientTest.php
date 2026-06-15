<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\ActiveIngredient\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientCreated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientDeactivated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientUpdated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PHPUnit\Framework\TestCase;

final class ActiveIngredientTest extends TestCase
{
    private function makeIngredient(?CasNumber $casNumber = null): ActiveIngredient
    {
        return ActiveIngredient::create(
            IngredientId::generate(),
            new IngredientName('Amoxicilina'),
            new DciCode('amoxicillin'),
            $casNumber,
            null,
            UserId::generate(),
        );
    }

    public function test_create_emits_ingredient_created_event(): void
    {
        $ingredient = $this->makeIngredient();

        $events = $ingredient->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(IngredientCreated::class, $events[0]);
    }

    public function test_create_with_null_cas_number_is_valid(): void
    {
        $ingredient = $this->makeIngredient(null);

        self::assertNull($ingredient->getCasNumber());
        $events = $ingredient->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(IngredientCreated::class, $events[0]);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $ingredient = ActiveIngredient::reconstitute(
            id: IngredientId::generate(),
            name: new IngredientName('Ibuprofeno'),
            dciCode: new DciCode('ibuprofen'),
            casNumber: null,
            description: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        self::assertEmpty($ingredient->releaseEvents());
    }

    public function test_update_changes_all_fields_and_emits_ingredient_updated(): void
    {
        $ingredient = $this->makeIngredient();
        $ingredient->releaseEvents();

        $ingredient->update(
            new IngredientName('Amoxicilina Modificada'),
            new DciCode('amoxicillin modified'),
            new CasNumber('26787-78-0'),
            'Nueva descripción',
        );

        self::assertSame('Amoxicilina Modificada', $ingredient->getName()->value);
        self::assertSame('amoxicillin modified', $ingredient->getDciCode()->value);
        self::assertSame('26787-78-0', $ingredient->getCasNumber()?->value);
        self::assertSame('Nueva descripción', $ingredient->getDescription());

        $events = $ingredient->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(IngredientUpdated::class, $events[0]);
    }

    public function test_update_sets_updated_at_to_current_time(): void
    {
        $ingredient = $this->makeIngredient();
        $before = $ingredient->getUpdatedAt();

        $ingredient->update(
            new IngredientName('Amoxicilina Modificada'),
            new DciCode('amoxicillin'),
            null,
            null,
        );

        self::assertGreaterThanOrEqual($before, $ingredient->getUpdatedAt());
    }

    public function test_deactivate_sets_is_active_false_and_emits_ingredient_deactivated(): void
    {
        $ingredient = $this->makeIngredient();
        $ingredient->releaseEvents();

        $ingredient->deactivate();

        self::assertFalse($ingredient->isActive());

        $events = $ingredient->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(IngredientDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $ingredient = $this->makeIngredient();
        $ingredient->deactivate();
        $ingredient->releaseEvents();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('ya está inactivo');

        $ingredient->deactivate();
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $ingredient = $this->makeIngredient();

        $first = $ingredient->releaseEvents();
        $second = $ingredient->releaseEvents();

        self::assertCount(1, $first);
        self::assertEmpty($second);
    }
}
