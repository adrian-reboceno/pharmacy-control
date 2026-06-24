<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Location\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;
use PharmaControl\Catalog\Location\Application\UseCase\CreateLocation\CreateLocationCommand;
use PharmaControl\Catalog\Location\Application\UseCase\CreateLocation\CreateLocationUseCase;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Location\Domain\Event\LocationCreated;
use PharmaControl\Catalog\Location\Domain\Exception\DuplicateLocationNameException;
use PharmaControl\Catalog\Location\Domain\Exception\LocationMaxDepthException;
use PharmaControl\Catalog\Location\Domain\Exception\LocationNotFoundException;
use PharmaControl\Catalog\Location\Domain\Model\Location;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationLevel;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateLocationUseCaseTest extends TestCase
{
    private LocationRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private CreateLocationUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LocationRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new CreateLocationUseCase($this->repository, $this->events);
    }

    private function makeZonaCommand(string $name = 'OTC General'): CreateLocationCommand
    {
        return new CreateLocationCommand(
            name: $name,
            level: 1,
            parentId: null,
            description: null,
            actorUserId: (string) UserId::generate(),
        );
    }

    private function makeZona(): Location
    {
        return Location::create(
            LocationId::generate(),
            LocationLevel::ZONA,
            null,
            new LocationName('Refrigeración'),
            null,
            UserId::generate(),
        );
    }

    private function stubDTOReturn(): void
    {
        $dto = new LocationDTO(
            id: (string) LocationId::generate(),
            name: 'OTC General',
            level: 1,
            levelLabel: 'Zona',
            parentId: null,
            description: null,
            isActive: true,
            isLeaf: false,
            fullPath: 'OTC General',
            createdBy: null,
            createdAt: (new \DateTimeImmutable)->format(\DateTimeInterface::ATOM),
            updatedAt: (new \DateTimeImmutable)->format(\DateTimeInterface::ATOM),
        );
        $this->repository->method('findByIdAsDTO')->willReturn($dto);
    }

    public function test_creates_zona_without_parent_and_returns_dto(): void
    {
        $this->repository->method('findByNameAndParent')->willReturn(null);
        $this->repository->expects($this->once())->method('save');
        $this->stubDTOReturn();

        $result = ($this->useCase)($this->makeZonaCommand());

        self::assertInstanceOf(LocationDTO::class, $result);
        self::assertSame(1, $result->level);
        self::assertNull($result->parentId);
    }

    public function test_throws_when_parent_id_given_for_zona(): void
    {
        $this->expectException(LocationMaxDepthException::class);

        ($this->useCase)(new CreateLocationCommand(
            name: 'Zona X',
            level: 1,
            parentId: (string) LocationId::generate(),
            description: null,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_throws_when_non_zona_created_without_parent(): void
    {
        $this->expectException(LocationMaxDepthException::class);

        ($this->useCase)(new CreateLocationCommand(
            name: 'Pasillo A',
            level: 2,
            parentId: null,
            description: null,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_throws_when_parent_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(LocationNotFoundException::class);

        ($this->useCase)(new CreateLocationCommand(
            name: 'Pasillo A',
            level: 2,
            parentId: (string) LocationId::generate(),
            description: null,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_throws_when_level_does_not_match_parent_child_level(): void
    {
        $zona = $this->makeZona();
        $this->repository->method('findById')->willReturn($zona);

        $this->expectException(LocationMaxDepthException::class);

        // Zona's childLevel is PASILLO(2), but we request ESTANTE(3)
        ($this->useCase)(new CreateLocationCommand(
            name: 'Estante 1',
            level: 3,
            parentId: $zona->getId()->value,
            description: null,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_throws_when_name_is_duplicate_among_siblings(): void
    {
        $this->repository->method('findByNameAndParent')->willReturn($this->makeZona());

        $this->expectException(DuplicateLocationNameException::class);

        ($this->useCase)($this->makeZonaCommand('Refrigeración'));
    }

    public function test_publishes_location_created_event(): void
    {
        $this->repository->method('findByNameAndParent')->willReturn(null);
        $this->repository->method('save');
        $this->stubDTOReturn();

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(LocationCreated::class));

        ($this->useCase)($this->makeZonaCommand());
    }

    public function test_creates_pasillo_with_valid_zona_parent(): void
    {
        $zona = $this->makeZona();
        $this->repository->method('findById')->willReturn($zona);
        $this->repository->method('findByNameAndParent')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $dto = new LocationDTO(
            id: (string) LocationId::generate(),
            name: 'Pasillo 1',
            level: 2,
            levelLabel: 'Pasillo',
            parentId: $zona->getId()->value,
            description: null,
            isActive: true,
            isLeaf: false,
            fullPath: 'Refrigeración > Pasillo 1',
            createdBy: null,
            createdAt: (new \DateTimeImmutable)->format(\DateTimeInterface::ATOM),
            updatedAt: (new \DateTimeImmutable)->format(\DateTimeInterface::ATOM),
        );
        $this->repository->method('findByIdAsDTO')->willReturn($dto);

        $result = ($this->useCase)(new CreateLocationCommand(
            name: 'Pasillo 1',
            level: 2,
            parentId: $zona->getId()->value,
            description: null,
            actorUserId: (string) UserId::generate(),
        ));

        self::assertSame(2, $result->level);
        self::assertSame($zona->getId()->value, $result->parentId);
        self::assertStringContainsString('Pasillo 1', $result->fullPath);
    }
}
