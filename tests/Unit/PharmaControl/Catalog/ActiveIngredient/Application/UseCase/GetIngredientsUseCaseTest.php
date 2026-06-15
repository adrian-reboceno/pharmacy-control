<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\ActiveIngredient\Application\UseCase;

use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\GetIngredients\GetIngredientsQuery;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\GetIngredients\GetIngredientsUseCase;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetIngredientsUseCaseTest extends TestCase
{
    private IngredientRepositoryContract&MockObject $repository;

    private GetIngredientsUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(IngredientRepositoryContract::class);
        $this->useCase = new GetIngredientsUseCase($this->repository);
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

    private function makeRepositoryResult(array $ingredients): array
    {
        return [
            'data' => $ingredients,
            'total' => count($ingredients),
            'per_page' => 20,
            'current_page' => 1,
            'last_page' => 1,
        ];
    }

    public function test_returns_paginated_list_with_default_filters(): void
    {
        $ingredients = [$this->makeIngredient()];
        $this->repository->method('findAll')->willReturn($this->makeRepositoryResult($ingredients));

        $result = ($this->useCase)(new GetIngredientsQuery);

        self::assertCount(1, $result['data']);
        self::assertInstanceOf(IngredientDTO::class, $result['data'][0]);
        self::assertSame(1, $result['total']);
    }

    public function test_filters_by_search_term_in_name(): void
    {
        $ingredients = [$this->makeIngredient('Amoxicilina', 'amoxicillin')];

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('search'))
            ->willReturn($this->makeRepositoryResult($ingredients));

        $result = ($this->useCase)(new GetIngredientsQuery(search: 'amox'));

        self::assertCount(1, $result['data']);
        self::assertSame('Amoxicilina', $result['data'][0]->name);
    }

    public function test_filters_by_search_term_in_dci_code(): void
    {
        $ingredients = [$this->makeIngredient('Ibuprofeno', 'ibuprofen')];

        $this->repository->method('findAll')->willReturn($this->makeRepositoryResult($ingredients));

        $result = ($this->useCase)(new GetIngredientsQuery(search: 'ibuprofen'));

        self::assertCount(1, $result['data']);
        self::assertSame('ibuprofen', $result['data'][0]->dciCode);
    }

    public function test_filters_by_search_term_in_cas_number(): void
    {
        $ingredient = ActiveIngredient::create(
            IngredientId::generate(),
            new IngredientName('Paracetamol'),
            new DciCode('paracetamol'),
            new CasNumber('103-90-2'),
            null,
            null,
        );

        $this->repository->method('findAll')->willReturn($this->makeRepositoryResult([$ingredient]));

        $result = ($this->useCase)(new GetIngredientsQuery(search: '103-90-2'));

        self::assertCount(1, $result['data']);
        self::assertSame('103-90-2', $result['data'][0]->casNumber);
    }

    public function test_filters_by_is_active(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($filters) => isset($filters['is_active']) && $filters['is_active'] === true))
            ->willReturn($this->makeRepositoryResult([]));

        ($this->useCase)(new GetIngredientsQuery(isActive: true));
    }

    public function test_returns_results_ordered_by_name_asc(): void
    {
        $ingredients = [
            $this->makeIngredient('Amoxicilina', 'amoxicillin'),
            $this->makeIngredient('Ibuprofeno', 'ibuprofen'),
        ];

        $this->repository->method('findAll')->willReturn($this->makeRepositoryResult($ingredients));

        $result = ($this->useCase)(new GetIngredientsQuery);

        self::assertSame('Amoxicilina', $result['data'][0]->name);
        self::assertSame('Ibuprofeno', $result['data'][1]->name);
    }
}
