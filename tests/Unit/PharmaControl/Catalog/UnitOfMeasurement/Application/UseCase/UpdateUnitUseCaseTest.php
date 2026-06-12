<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\UpdateUnit\UpdateUnitCommand;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\UpdateUnit\UpdateUnitUseCase;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitUpdated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitNameException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitSymbolException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\UnitNotFoundException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateUnitUseCaseTest extends TestCase
{
    private UnitRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private UpdateUnitUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UnitRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new UpdateUnitUseCase($this->repository, $this->events);
    }

    private function makeUnit(string $name = 'Miligramo', string $symbol = 'mg'): UnitOfMeasurement
    {
        $unit = UnitOfMeasurement::create(
            UnitId::generate(),
            new UnitName($name),
            new UnitSymbol($symbol),
            UnitType::CONCENTRATION,
            UserId::generate(),
        );
        $unit->releaseEvents();

        return $unit;
    }

    private function makeCommand(string $id, string $name = 'Gramo', string $symbol = 'g', string $type = 'CONCENTRATION'): UpdateUnitCommand
    {
        return new UpdateUnitCommand(
            id: $id,
            name: $name,
            symbol: $symbol,
            type: $type,
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_updates_unit_and_returns_dto(): void
    {
        $unit = $this->makeUnit();
        $this->repository->method('findById')->willReturn($unit);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findBySymbol')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($unit->getId()->value));

        self::assertInstanceOf(UnitDTO::class, $result);
        self::assertSame('Gramo', $result->name);
    }

    public function test_throws_unit_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(UnitNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_duplicate_unit_name_exception_when_name_belongs_to_different_unit(): void
    {
        $unit = $this->makeUnit('Miligramo', 'mg');
        $other = $this->makeUnit('Gramo', 'g');

        $this->repository->method('findById')->willReturn($unit);
        $this->repository->method('findByName')->willReturn($other);
        $this->repository->method('findBySymbol')->willReturn(null);

        $this->expectException(DuplicateUnitNameException::class);

        ($this->useCase)($this->makeCommand($unit->getId()->value, 'Gramo', 'x'));
    }

    public function test_allows_keeping_same_name_on_own_unit(): void
    {
        $unit = $this->makeUnit('Miligramo', 'mg');

        $this->repository->method('findById')->willReturn($unit);
        $this->repository->method('findByName')->willReturn($unit);
        $this->repository->method('findBySymbol')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($unit->getId()->value, 'Miligramo', 'mg'));

        self::assertSame('Miligramo', $result->name);
    }

    public function test_throws_duplicate_unit_symbol_exception_when_symbol_belongs_to_different_unit(): void
    {
        $unit = $this->makeUnit('Miligramo', 'mg');
        $other = $this->makeUnit('Gramo', 'g');

        $this->repository->method('findById')->willReturn($unit);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findBySymbol')->willReturn($other);

        $this->expectException(DuplicateUnitSymbolException::class);

        ($this->useCase)($this->makeCommand($unit->getId()->value, 'X', 'g'));
    }

    public function test_allows_keeping_same_symbol_on_own_unit(): void
    {
        $unit = $this->makeUnit('Miligramo', 'mg');

        $this->repository->method('findById')->willReturn($unit);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findBySymbol')->willReturn($unit);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($unit->getId()->value, 'Miligramo', 'mg'));

        self::assertSame('mg', $result->symbol);
    }

    public function test_publishes_unit_updated_event(): void
    {
        $unit = $this->makeUnit();

        $this->repository->method('findById')->willReturn($unit);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findBySymbol')->willReturn(null);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(UnitUpdated::class));

        ($this->useCase)($this->makeCommand($unit->getId()->value));
    }
}
