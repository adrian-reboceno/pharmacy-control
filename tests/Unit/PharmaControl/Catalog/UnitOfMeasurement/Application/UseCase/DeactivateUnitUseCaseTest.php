<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\DeactivateUnit\DeactivateUnitCommand;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\DeactivateUnit\DeactivateUnitUseCase;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitDeactivated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\UnitNotFoundException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivateUnitUseCaseTest extends TestCase
{
    private UnitRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private DeactivateUnitUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UnitRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new DeactivateUnitUseCase($this->repository, $this->events);
    }

    private function makeUnit(): UnitOfMeasurement
    {
        $unit = UnitOfMeasurement::create(
            UnitId::generate(),
            new UnitName('Miligramo'),
            new UnitSymbol('mg'),
            UnitType::CONCENTRATION,
            UserId::generate(),
        );
        $unit->releaseEvents();

        return $unit;
    }

    public function test_deactivates_active_unit(): void
    {
        $unit = $this->makeUnit();
        $this->repository->method('findById')->willReturn($unit);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)(new DeactivateUnitCommand($unit->getId()->value, (string) UserId::generate()));

        self::assertFalse($unit->isActive());
    }

    public function test_throws_unit_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(UnitNotFoundException::class);

        ($this->useCase)(new DeactivateUnitCommand('00000000-0000-4000-8000-000000000000', (string) UserId::generate()));
    }

    public function test_throws_domain_exception_when_already_inactive(): void
    {
        $unit = $this->makeUnit();
        $unit->deactivate();
        $unit->releaseEvents();

        $this->repository->method('findById')->willReturn($unit);

        $this->expectException(\DomainException::class);

        ($this->useCase)(new DeactivateUnitCommand($unit->getId()->value, (string) UserId::generate()));
    }

    public function test_publishes_unit_deactivated_event(): void
    {
        $unit = $this->makeUnit();
        $this->repository->method('findById')->willReturn($unit);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(UnitDeactivated::class));

        ($this->useCase)(new DeactivateUnitCommand($unit->getId()->value, (string) UserId::generate()));
    }
}
