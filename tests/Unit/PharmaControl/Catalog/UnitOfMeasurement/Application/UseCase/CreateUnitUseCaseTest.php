<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\CreateUnit\CreateUnitCommand;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\CreateUnit\CreateUnitUseCase;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitCreated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitNameException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitSymbolException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateUnitUseCaseTest extends TestCase
{
    private UnitRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private CreateUnitUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UnitRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new CreateUnitUseCase($this->repository, $this->events);
    }

    private function makeCommand(string $name = 'Miligramo', string $symbol = 'mg', string $type = 'CONCENTRATION'): CreateUnitCommand
    {
        return new CreateUnitCommand(
            name: $name,
            symbol: $symbol,
            type: $type,
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_creates_unit_and_returns_dto_on_success(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findBySymbol')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand());

        self::assertInstanceOf(UnitDTO::class, $result);
        self::assertSame('Miligramo', $result->name);
        self::assertSame('mg', $result->symbol);
        self::assertSame('CONCENTRATION', $result->type);
        self::assertTrue($result->isActive);
    }

    public function test_throws_duplicate_unit_name_exception_when_name_already_exists_case_insensitive(): void
    {
        $existing = UnitOfMeasurement::create(
            UnitId::generate(),
            new UnitName('MILIGRAMO'),
            new UnitSymbol('MG'),
            UnitType::CONCENTRATION,
            null,
        );

        $this->repository->method('findByName')->willReturn($existing);
        $this->repository->method('findBySymbol')->willReturn(null);

        $this->expectException(DuplicateUnitNameException::class);

        ($this->useCase)($this->makeCommand('Miligramo'));
    }

    public function test_throws_duplicate_unit_symbol_exception_when_symbol_already_exists(): void
    {
        $existing = UnitOfMeasurement::create(
            UnitId::generate(),
            new UnitName('Otro nombre'),
            new UnitSymbol('mg'),
            UnitType::CONCENTRATION,
            null,
        );

        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findBySymbol')->willReturn($existing);

        $this->expectException(DuplicateUnitSymbolException::class);

        ($this->useCase)($this->makeCommand());
    }

    public function test_publishes_unit_created_event(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findBySymbol')->willReturn(null);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(UnitCreated::class));

        ($this->useCase)($this->makeCommand());
    }
}
