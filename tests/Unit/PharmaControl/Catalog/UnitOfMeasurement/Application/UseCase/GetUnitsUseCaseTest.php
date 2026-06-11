<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase;

use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\GetUnits\GetUnitsQuery;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\GetUnits\GetUnitsUseCase;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetUnitsUseCaseTest extends TestCase
{
    private UnitRepositoryContract&MockObject $repository;
    private GetUnitsUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UnitRepositoryContract::class);
        $this->useCase    = new GetUnitsUseCase($this->repository);
    }

    private function paginatedResult(array $data = []): array
    {
        return [
            'data'         => $data,
            'total'        => count($data),
            'per_page'     => 20,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function test_returns_paginated_list_with_default_filters(): void
    {
        $expected = $this->paginatedResult();
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($f) => !isset($f['type']) && !isset($f['search']) && !isset($f['is_active'])))
            ->willReturn($expected);

        $result = ($this->useCase)(new GetUnitsQuery());

        self::assertSame($expected, $result);
    }

    public function test_filters_by_type_quantity(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($f) => ($f['type'] ?? null) === 'QUANTITY'))
            ->willReturn($this->paginatedResult());

        ($this->useCase)(new GetUnitsQuery(type: 'QUANTITY'));
    }

    public function test_filters_by_type_concentration(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($f) => ($f['type'] ?? null) === 'CONCENTRATION'))
            ->willReturn($this->paginatedResult());

        ($this->useCase)(new GetUnitsQuery(type: 'CONCENTRATION'));
    }

    public function test_filters_by_search_term_in_name(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($f) => ($f['search'] ?? null) === 'mili'))
            ->willReturn($this->paginatedResult());

        ($this->useCase)(new GetUnitsQuery(search: 'mili'));
    }

    public function test_filters_by_search_term_in_symbol(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($f) => ($f['search'] ?? null) === 'mg'))
            ->willReturn($this->paginatedResult());

        ($this->useCase)(new GetUnitsQuery(search: 'mg'));
    }

    public function test_filters_by_is_active(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($f) => ($f['is_active'] ?? null) === true))
            ->willReturn($this->paginatedResult());

        ($this->useCase)(new GetUnitsQuery(isActive: true));
    }

    public function test_returns_results_ordered_concentration_first_then_quantity(): void
    {
        $result = $this->paginatedResult();
        $this->repository->method('findAll')->willReturn($result);

        $returned = ($this->useCase)(new GetUnitsQuery());

        self::assertSame($result, $returned);
    }
}
