<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Presentations\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationCreated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationDeactivated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationUpdated;
use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PHPUnit\Framework\TestCase;

final class PresentationTest extends TestCase
{
    private function makePresentation(
        string $name = 'Tableta',
        string $abbreviation = 'Tab',
    ): Presentation {
        return Presentation::create(
            PresentationId::generate(),
            new PresentationName($name),
            new Abbreviation($abbreviation),
            'Descripción de prueba',
            UserId::generate(),
        );
    }

    public function test_create_emits_presentation_created_event(): void
    {
        $p = $this->makePresentation();
        $events = $p->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(PresentationCreated::class, $events[0]);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $p = Presentation::reconstitute(
            id: PresentationId::generate(),
            name: new PresentationName('Tableta'),
            abbreviation: new Abbreviation('Tab'),
            description: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        self::assertSame([], $p->releaseEvents());
    }

    public function test_update_changes_name_abbreviation_and_description_and_emits_presentation_updated(): void
    {
        $p = $this->makePresentation('Tableta', 'Tab');
        $p->releaseEvents();

        $p->update(new PresentationName('Cápsula'), new Abbreviation('Cap'), 'Nueva descripción');
        $events = $p->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(PresentationUpdated::class, $events[0]);
        self::assertSame('Cápsula', $p->getName()->value);
        self::assertSame('Cap', $p->getAbbreviation()->value);
        self::assertSame('Nueva descripción', $p->getDescription());
    }

    public function test_update_sets_updated_at_to_current_time(): void
    {
        $before = new \DateTimeImmutable;
        $p = $this->makePresentation();
        $p->releaseEvents();

        $p->update(new PresentationName('Cápsula'), new Abbreviation('Cap'), null);

        self::assertGreaterThanOrEqual($before, $p->getUpdatedAt());
    }

    public function test_deactivate_sets_is_active_false_and_emits_presentation_deactivated(): void
    {
        $p = $this->makePresentation();
        $p->releaseEvents();

        $p->deactivate();
        $events = $p->releaseEvents();

        self::assertFalse($p->isActive());
        self::assertCount(1, $events);
        self::assertInstanceOf(PresentationDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $p = $this->makePresentation();
        $p->releaseEvents();
        $p->deactivate();
        $p->releaseEvents();

        $this->expectException(\DomainException::class);

        $p->deactivate();
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $p = $this->makePresentation();

        $first = $p->releaseEvents();
        $second = $p->releaseEvents();

        self::assertCount(1, $first);
        self::assertCount(0, $second);
    }
}
