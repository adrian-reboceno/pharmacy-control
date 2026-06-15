<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\ActiveIngredient\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\UpdateIngredient\UpdateIngredientCommand;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\UpdateIngredient\UpdateIngredientUseCase;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientUpdated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateCasNumberException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateDciCodeException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateIngredientNameException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\IngredientNotFoundException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateIngredientUseCaseTest extends TestCase
{
    private IngredientRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private UpdateIngredientUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(IngredientRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new UpdateIngredientUseCase($this->repository, $this->events);
    }

    private function makeIngredient(string $name = 'Amoxicilina', string $dci = 'amoxicillin'): ActiveIngredient
    {
        return ActiveIngredient::create(
            IngredientId::generate(),
            new IngredientName($name),
            new DciCode($dci),
            null,
            null,
            null,
        );
    }

    private function makeCommand(string $id, string $name = 'Amoxicilina Updated', string $dci = 'amoxicillin updated'): UpdateIngredientCommand
    {
        return new UpdateIngredientCommand(
            id: $id,
            name: $name,
            dciCode: $dci,
            casNumber: null,
            description: null,
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_updates_ingredient_and_returns_dto(): void
    {
        $ingredient = $this->makeIngredient();
        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($ingredient->getId()->value));

        self::assertInstanceOf(IngredientDTO::class, $result);
        self::assertSame('Amoxicilina Updated', $result->name);
    }

    public function test_throws_ingredient_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(IngredientNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_allows_keeping_same_name_on_own_ingredient(): void
    {
        $ingredient = $this->makeIngredient('Amoxicilina', 'amoxicillin');
        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('findByName')->willReturn($ingredient);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)(new UpdateIngredientCommand(
            id: $ingredient->getId()->value,
            name: 'Amoxicilina',
            dciCode: 'amoxicillin updated',
            casNumber: null,
            description: null,
            actorUserId: (string) UserId::generate(),
        ));

        self::assertInstanceOf(IngredientDTO::class, $result);
    }

    public function test_allows_keeping_same_dci_on_own_ingredient(): void
    {
        $ingredient = $this->makeIngredient('Amoxicilina', 'amoxicillin');
        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn($ingredient);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)(new UpdateIngredientCommand(
            id: $ingredient->getId()->value,
            name: 'Amoxicilina Updated',
            dciCode: 'amoxicillin',
            casNumber: null,
            description: null,
            actorUserId: (string) UserId::generate(),
        ));

        self::assertInstanceOf(IngredientDTO::class, $result);
    }

    public function test_allows_keeping_same_cas_on_own_ingredient(): void
    {
        $ingredient = ActiveIngredient::create(
            IngredientId::generate(),
            new IngredientName('Amoxicilina'),
            new DciCode('amoxicillin'),
            new CasNumber('26787-78-0'),
            null,
            null,
        );

        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->method('findByCasNumber')->willReturn($ingredient);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)(new UpdateIngredientCommand(
            id: $ingredient->getId()->value,
            name: 'Amoxicilina',
            dciCode: 'amoxicillin',
            casNumber: '26787-78-0',
            description: null,
            actorUserId: (string) UserId::generate(),
        ));

        self::assertInstanceOf(IngredientDTO::class, $result);
    }

    public function test_throws_duplicate_ingredient_name_exception_when_name_belongs_to_different_ingredient(): void
    {
        $ingredient = $this->makeIngredient('Amoxicilina', 'amoxicillin');
        $other = $this->makeIngredient('Ibuprofeno', 'ibuprofen');

        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('findByName')->willReturn($other);

        $this->expectException(DuplicateIngredientNameException::class);

        ($this->useCase)(new UpdateIngredientCommand(
            id: $ingredient->getId()->value,
            name: 'Ibuprofeno',
            dciCode: 'amoxicillin',
            casNumber: null,
            description: null,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_throws_duplicate_dci_code_exception_when_dci_belongs_to_different_ingredient(): void
    {
        $ingredient = $this->makeIngredient('Amoxicilina', 'amoxicillin');
        $other = $this->makeIngredient('Ibuprofeno', 'ibuprofen');

        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn($other);

        $this->expectException(DuplicateDciCodeException::class);

        ($this->useCase)(new UpdateIngredientCommand(
            id: $ingredient->getId()->value,
            name: 'Amoxicilina',
            dciCode: 'ibuprofen',
            casNumber: null,
            description: null,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_throws_duplicate_cas_number_exception_when_cas_belongs_to_different_ingredient(): void
    {
        $ingredient = $this->makeIngredient();
        $other = $this->makeIngredient('Ibuprofeno', 'ibuprofen');

        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->method('findByCasNumber')->willReturn($other);

        $this->expectException(DuplicateCasNumberException::class);

        ($this->useCase)(new UpdateIngredientCommand(
            id: $ingredient->getId()->value,
            name: 'Amoxicilina',
            dciCode: 'amoxicillin',
            casNumber: '15687-27-1',
            description: null,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_publishes_ingredient_updated_event(): void
    {
        $ingredient = $this->makeIngredient();
        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(IngredientUpdated::class));

        ($this->useCase)($this->makeCommand($ingredient->getId()->value));
    }
}
