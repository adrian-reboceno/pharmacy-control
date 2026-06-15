<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\ActiveIngredient\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\DeactivateIngredient\DeactivateIngredientCommand;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\DeactivateIngredient\DeactivateIngredientUseCase;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientDeactivated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\IngredientNotFoundException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivateIngredientUseCaseTest extends TestCase
{
    private IngredientRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private DeactivateIngredientUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(IngredientRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new DeactivateIngredientUseCase($this->repository, $this->events);
    }

    private function makeIngredient(): ActiveIngredient
    {
        return ActiveIngredient::create(
            IngredientId::generate(),
            new IngredientName('Amoxicilina'),
            new DciCode('amoxicillin'),
            null,
            null,
            null,
        );
    }

    private function makeCommand(string $id): DeactivateIngredientCommand
    {
        return new DeactivateIngredientCommand($id, (string) UserId::generate());
    }

    public function test_deactivates_active_ingredient(): void
    {
        $ingredient = $this->makeIngredient();
        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)($this->makeCommand($ingredient->getId()->value));

        self::assertFalse($ingredient->isActive());
    }

    public function test_throws_ingredient_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(IngredientNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_domain_exception_when_already_inactive(): void
    {
        $ingredient = $this->makeIngredient();
        $ingredient->deactivate();
        $ingredient->releaseEvents();

        $this->repository->method('findById')->willReturn($ingredient);

        $this->expectException(\DomainException::class);

        ($this->useCase)($this->makeCommand($ingredient->getId()->value));
    }

    public function test_publishes_ingredient_deactivated_event(): void
    {
        $ingredient = $this->makeIngredient();
        $this->repository->method('findById')->willReturn($ingredient);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(IngredientDeactivated::class));

        ($this->useCase)($this->makeCommand($ingredient->getId()->value));
    }
}
