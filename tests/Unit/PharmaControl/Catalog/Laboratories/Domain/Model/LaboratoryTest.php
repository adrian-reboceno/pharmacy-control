<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Laboratories/Domain/Model/LaboratoryTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Laboratories\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryCreated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryDeactivated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryUpdated;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\WebsiteUrl;
use PHPUnit\Framework\TestCase;

final class LaboratoryTest extends TestCase
{
    private function makeLaboratory(?WebsiteUrl $website = null): Laboratory
    {
        return Laboratory::create(
            LaboratoryId::generate(),
            new LaboratoryName('Bayer'),
            new CountryCode('DE'),
            $website,
            UserId::generate(),
        );
    }

    public function test_creates_laboratory_with_is_active_true_and_emits_laboratory_created(): void
    {
        $lab = $this->makeLaboratory();

        self::assertTrue($lab->isActive());

        $events = $lab->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(LaboratoryCreated::class, $events[0]);
    }

    public function test_create_emits_exactly_one_event(): void
    {
        $lab = $this->makeLaboratory();
        $events = $lab->releaseEvents();

        self::assertCount(1, $events);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $lab = Laboratory::reconstitute(
            id: LaboratoryId::generate(),
            name: new LaboratoryName('Pfizer'),
            countryCode: new CountryCode('US'),
            website: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        self::assertEmpty($lab->releaseEvents());
    }

    public function test_update_changes_name_country_and_website_and_emits_laboratory_updated(): void
    {
        $lab = $this->makeLaboratory();
        $lab->releaseEvents();

        $lab->update(
            new LaboratoryName('Bayer AG'),
            new CountryCode('CH'),
            new WebsiteUrl('https://www.bayer.com'),
        );

        self::assertSame('Bayer AG', $lab->getName()->value);
        self::assertSame('CH', $lab->getCountryCode()->value);
        self::assertSame('https://www.bayer.com', $lab->getWebsite()?->value);

        $events = $lab->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(LaboratoryUpdated::class, $events[0]);
    }

    public function test_deactivate_sets_is_active_false_and_emits_laboratory_deactivated(): void
    {
        $lab = $this->makeLaboratory();
        $lab->releaseEvents();

        $lab->deactivate();

        self::assertFalse($lab->isActive());

        $events = $lab->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(LaboratoryDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $lab = $this->makeLaboratory();
        $lab->deactivate();
        $lab->releaseEvents();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('El laboratorio ya está inactivo.');

        $lab->deactivate();
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $lab = $this->makeLaboratory();

        $first = $lab->releaseEvents();
        $second = $lab->releaseEvents();

        self::assertCount(1, $first);
        self::assertEmpty($second);
    }
}
