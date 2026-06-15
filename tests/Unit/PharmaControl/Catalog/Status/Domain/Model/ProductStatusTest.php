<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Status\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Status\Domain\Event\StatusCreated;
use PharmaControl\Catalog\Status\Domain\Event\StatusDeactivated;
use PharmaControl\Catalog\Status\Domain\Event\StatusUpdated;
use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PHPUnit\Framework\TestCase;

final class ProductStatusTest extends TestCase
{
    private function makeId(): StatusId
    {
        return StatusId::generate();
    }

    private function makeName(string $name = 'Activo'): StatusName
    {
        return new StatusName($name);
    }

    private function makeCode(string $code = 'ACTIVO'): StatusCode
    {
        return new StatusCode($code);
    }

    private function makeUserId(): UserId
    {
        return new UserId('00000000-0000-4000-8000-000000000001');
    }

    public function test_create_emits_status_created_event(): void
    {
        $status = ProductStatus::create(
            $this->makeId(),
            $this->makeName(),
            $this->makeCode(),
            'Disponible para venta.',
            $this->makeUserId(),
        );

        $events = $status->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(StatusCreated::class, $events[0]);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $status = ProductStatus::reconstitute(
            $this->makeId(),
            $this->makeName(),
            $this->makeCode(),
            null,
            true,
            null,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
        );

        $events = $status->releaseEvents();

        $this->assertCount(0, $events);
    }

    public function test_update_changes_name_code_and_description_and_emits_status_updated(): void
    {
        $status = ProductStatus::create(
            $this->makeId(),
            $this->makeName('Activo'),
            $this->makeCode('ACTIVO'),
            'Original.',
            $this->makeUserId(),
        );
        $status->releaseEvents();

        $status->update(
            new StatusName('Descontinuado'),
            new StatusCode('DESCONTINUADO'),
            'Cambiado.',
        );

        $this->assertSame('Descontinuado', $status->getName()->value);
        $this->assertSame('DESCONTINUADO', $status->getCode()->value);
        $this->assertSame('Cambiado.', $status->getDescription());

        $events = $status->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(StatusUpdated::class, $events[0]);
    }

    public function test_update_sets_updated_at_to_current_time(): void
    {
        $before = new \DateTimeImmutable();

        $status = ProductStatus::create(
            $this->makeId(),
            $this->makeName(),
            $this->makeCode(),
            null,
            $this->makeUserId(),
        );
        $status->releaseEvents();

        $status->update($this->makeName('Eliminado'), $this->makeCode('ELIMINADO'), null);

        $this->assertGreaterThanOrEqual($before, $status->getUpdatedAt());
    }

    public function test_deactivate_sets_is_active_false_and_emits_status_deactivated(): void
    {
        $status = ProductStatus::create(
            $this->makeId(),
            $this->makeName(),
            $this->makeCode(),
            null,
            $this->makeUserId(),
        );
        $status->releaseEvents();

        $status->deactivate();

        $this->assertFalse($status->isActive());

        $events = $status->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(StatusDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $status = ProductStatus::reconstitute(
            $this->makeId(),
            $this->makeName(),
            $this->makeCode(),
            null,
            false,
            null,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
        );

        $this->expectException(\DomainException::class);

        $status->deactivate();
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $status = ProductStatus::create(
            $this->makeId(),
            $this->makeName(),
            $this->makeCode(),
            null,
            $this->makeUserId(),
        );

        $first  = $status->releaseEvents();
        $second = $status->releaseEvents();

        $this->assertCount(1, $first);
        $this->assertCount(0, $second);
    }
}
