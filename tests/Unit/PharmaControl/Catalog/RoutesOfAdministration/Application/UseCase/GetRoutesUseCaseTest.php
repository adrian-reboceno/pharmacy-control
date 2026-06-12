<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase;

use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\GetRoutes\GetRoutesQuery;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\GetRoutes\GetRoutesUseCase;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetRoutesUseCaseTest extends TestCase
{
    private RouteRepositoryContract&MockObject $repository;

    private GetRoutesUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RouteRepositoryContract::class);
        $this->useCase = new GetRoutesUseCase($this->repository);
    }

    private function makeRoute(string $name, string $code): RouteOfAdministration
    {
        return RouteOfAdministration::create(
            RouteId::generate(),
            new RouteName($name),
            new RouteCode($code),
            null,
            null,
        );
    }

    private function makePagedResult(array $routes): array
    {
        return [
            'data' => $routes,
            'total' => count($routes),
            'per_page' => 20,
            'current_page' => 1,
            'last_page' => 1,
        ];
    }

    public function test_returns_paginated_list_with_default_filters(): void
    {
        $routes = [
            $this->makeRoute('Intravenosa', 'IV'),
            $this->makeRoute('Oral', 'VO'),
        ];
        $this->repository->method('findAll')->willReturn($this->makePagedResult($routes));

        $result = ($this->useCase)(new GetRoutesQuery);

        self::assertCount(2, $result['data']);
        self::assertContainsOnlyInstancesOf(RouteDTO::class, $result['data']);
        self::assertSame(2, $result['total']);
    }

    public function test_filters_by_search_term_in_name(): void
    {
        $iv = $this->makeRoute('Intravenosa', 'IV');
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('search'))
            ->willReturn($this->makePagedResult([$iv]));

        $result = ($this->useCase)(new GetRoutesQuery(search: 'Intra'));

        self::assertCount(1, $result['data']);
        self::assertSame('Intravenosa', $result['data'][0]->name);
    }

    public function test_filters_by_search_term_in_code(): void
    {
        $iv = $this->makeRoute('Intravenosa', 'IV');
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('search'))
            ->willReturn($this->makePagedResult([$iv]));

        $result = ($this->useCase)(new GetRoutesQuery(search: 'IV'));

        self::assertCount(1, $result['data']);
        self::assertSame('IV', $result['data'][0]->code);
    }

    public function test_filters_by_is_active(): void
    {
        $active = $this->makeRoute('Oral', 'VO');
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('is_active'))
            ->willReturn($this->makePagedResult([$active]));

        $result = ($this->useCase)(new GetRoutesQuery(isActive: true));

        self::assertCount(1, $result['data']);
        self::assertTrue($result['data'][0]->isActive);
    }

    public function test_returns_results_ordered_by_name_asc(): void
    {
        $intra = $this->makeRoute('Intravenosa', 'IV');
        $oral = $this->makeRoute('Oral', 'VO');

        $this->repository->method('findAll')->willReturn($this->makePagedResult([$intra, $oral]));

        $result = ($this->useCase)(new GetRoutesQuery);

        self::assertSame('Intravenosa', $result['data'][0]->name);
        self::assertSame('Oral', $result['data'][1]->name);
    }
}
