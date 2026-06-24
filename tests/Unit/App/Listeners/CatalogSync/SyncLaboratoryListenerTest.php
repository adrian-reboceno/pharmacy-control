<?php

declare(strict_types=1);

namespace Tests\Unit\App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncLaboratoryToMongoJob;
use App\Listeners\CatalogSync\SyncLaboratoryListener;
use Illuminate\Support\Facades\Queue;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryCreated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryDeactivated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryUpdated;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class SyncLaboratoryListenerTest extends TestCase
{
    private LaboratoryRepositoryContract&MockObject $repository;

    private SyncLaboratoryListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(LaboratoryRepositoryContract::class);
        $this->listener = new SyncLaboratoryListener($this->repository);
    }

    /** @test */
    public function it_dispatches_job_on_laboratory_created_event(): void
    {
        Queue::fake();

        $laboratory = $this->buildMockLaboratory();
        $id = new LaboratoryId('550e8400-e29b-41d4-a716-446655440000');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($laboratory);

        $event = new LaboratoryCreated(
            id: $id,
            name: new LaboratoryName('Lab Prueba'),
            countryCode: new CountryCode('MX'),
            createdBy: null,
            occurredAt: new \DateTimeImmutable,
        );

        $this->listener->handleCreated($event);

        Queue::assertPushed(SyncLaboratoryToMongoJob::class);
    }

    /** @test */
    public function it_dispatches_job_on_laboratory_updated_event(): void
    {
        Queue::fake();

        $laboratory = $this->buildMockLaboratory();
        $id = new LaboratoryId('550e8400-e29b-41d4-a716-446655440000');

        $this->repository->expects($this->once())
            ->method('findById')
            ->willReturn($laboratory);

        $event = new LaboratoryUpdated(
            id: $id,
            previousName: new LaboratoryName('Lab Anterior'),
            newName: new LaboratoryName('Lab Nuevo'),
            previousCountryCode: new CountryCode('MX'),
            newCountryCode: new CountryCode('US'),
            previousWebsite: null,
            newWebsite: null,
            occurredAt: new \DateTimeImmutable,
        );

        $this->listener->handleUpdated($event);

        Queue::assertPushed(SyncLaboratoryToMongoJob::class);
    }

    /** @test */
    public function it_dispatches_job_on_laboratory_deactivated_event(): void
    {
        Queue::fake();

        $laboratory = $this->buildMockLaboratory();
        $id = new LaboratoryId('550e8400-e29b-41d4-a716-446655440000');

        $this->repository->expects($this->once())
            ->method('findById')
            ->willReturn($laboratory);

        $event = new LaboratoryDeactivated(
            id: $id,
            occurredAt: new \DateTimeImmutable,
        );

        $this->listener->handleDeactivated($event);

        Queue::assertPushed(SyncLaboratoryToMongoJob::class);
    }

    /** @test */
    public function it_does_not_dispatch_job_when_laboratory_no_longer_exists(): void
    {
        Queue::fake();

        $id = new LaboratoryId('550e8400-e29b-41d4-a716-446655440000');

        $this->repository->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $event = new LaboratoryCreated(
            id: $id,
            name: new LaboratoryName('Lab Borrado'),
            countryCode: new CountryCode('MX'),
            createdBy: null,
            occurredAt: new \DateTimeImmutable,
        );

        $this->listener->handleCreated($event);

        Queue::assertNotPushed(SyncLaboratoryToMongoJob::class);
    }

    /** @test */
    public function it_rereads_full_entity_from_repository_not_event_data(): void
    {
        Queue::fake();

        $laboratory = $this->buildMockLaboratory('Nombre Completo Desde Repo');
        $id = new LaboratoryId('550e8400-e29b-41d4-a716-446655440000');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($laboratory);

        $event = new LaboratoryCreated(
            id: $id,
            name: new LaboratoryName('Nombre Parcial Del Evento'),
            countryCode: new CountryCode('MX'),
            createdBy: null,
            occurredAt: new \DateTimeImmutable,
        );

        $this->listener->handleCreated($event);

        Queue::assertPushed(SyncLaboratoryToMongoJob::class, function (SyncLaboratoryToMongoJob $job) {
            $doc = (fn () => $this->document)->call($job);

            return $doc['name'] === 'Nombre Completo Desde Repo';
        });
    }

    private function buildMockLaboratory(string $name = 'Lab Test'): object
    {
        $mock = $this->createMock(Laboratory::class);
        $mock->method('getId')->willReturn(new LaboratoryId('550e8400-e29b-41d4-a716-446655440000'));
        $mock->method('getName')->willReturn(new LaboratoryName($name));
        $mock->method('getCountryCode')->willReturn(new CountryCode('MX'));
        $mock->method('getWebsite')->willReturn(null);
        $mock->method('isActive')->willReturn(true);
        $mock->method('getCreatedBy')->willReturn(null);
        $mock->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-01-01'));
        $mock->method('getUpdatedAt')->willReturn(new \DateTimeImmutable('2026-01-01'));

        return $mock;
    }
}
