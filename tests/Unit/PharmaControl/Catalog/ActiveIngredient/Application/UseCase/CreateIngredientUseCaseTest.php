<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\ActiveIngredient\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\CreateIngredient\CreateIngredientCommand;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\CreateIngredient\CreateIngredientUseCase;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientCreated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateCasNumberException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateDciCodeException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateIngredientNameException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateIngredientUseCaseTest extends TestCase
{
    private IngredientRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private CreateIngredientUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(IngredientRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new CreateIngredientUseCase($this->repository, $this->events);
    }

    private function makeCommand(
        string $name = 'Amoxicilina',
        string $dciCode = 'amoxicillin',
        ?string $casNumber = '26787-78-0',
    ): CreateIngredientCommand {
        return new CreateIngredientCommand(
            name: $name,
            dciCode: $dciCode,
            casNumber: $casNumber,
            description: null,
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_creates_ingredient_with_cas_number_and_returns_dto(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->method('findByCasNumber')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand());

        self::assertInstanceOf(IngredientDTO::class, $result);
        self::assertSame('Amoxicilina', $result->name);
        self::assertSame('amoxicillin', $result->dciCode);
        self::assertSame('26787-78-0', $result->casNumber);
        self::assertTrue($result->isActive);
    }

    public function test_creates_ingredient_without_cas_number_and_returns_dto(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand(casNumber: null));

        self::assertInstanceOf(IngredientDTO::class, $result);
        self::assertNull($result->casNumber);
    }

    public function test_throws_duplicate_ingredient_name_exception_when_name_exists_case_insensitive(): void
    {
        $existing = ActiveIngredient::create(
            IngredientId::generate(),
            new IngredientName('Amoxicilina'),
            new DciCode('amoxicillin'),
            null,
            null,
            null,
        );

        $this->repository->method('findByName')->willReturn($existing);

        $this->expectException(DuplicateIngredientNameException::class);

        ($this->useCase)($this->makeCommand(name: 'AMOXICILINA'));
    }

    public function test_throws_duplicate_dci_code_exception_when_dci_code_exists_case_insensitive(): void
    {
        $existing = ActiveIngredient::create(
            IngredientId::generate(),
            new IngredientName('Amoxicilina'),
            new DciCode('amoxicillin'),
            null,
            null,
            null,
        );

        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn($existing);

        $this->expectException(DuplicateDciCodeException::class);

        ($this->useCase)($this->makeCommand(dciCode: 'AMOXICILLIN'));
    }

    public function test_throws_duplicate_cas_number_exception_when_cas_number_exists_and_is_not_null(): void
    {
        $existing = ActiveIngredient::create(
            IngredientId::generate(),
            new IngredientName('Otro'),
            new DciCode('other'),
            null,
            null,
            null,
        );

        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->method('findByCasNumber')->willReturn($existing);

        $this->expectException(DuplicateCasNumberException::class);

        ($this->useCase)($this->makeCommand(casNumber: '26787-78-0'));
    }

    public function test_does_not_check_cas_duplicate_when_cas_is_null(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->expects($this->never())->method('findByCasNumber');
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)($this->makeCommand(casNumber: null));
    }

    public function test_publishes_ingredient_created_event(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByDciCode')->willReturn(null);
        $this->repository->method('findByCasNumber')->willReturn(null);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(IngredientCreated::class));

        ($this->useCase)($this->makeCommand());
    }
}
