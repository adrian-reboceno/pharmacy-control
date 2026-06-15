<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Status\Application\UseCase;

use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Application\UseCase\GetStatuses\GetStatusesQuery;
use PharmaControl\Catalog\Status\Application\UseCase\GetStatuses\GetStatusesUseCase;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetStatusesUseCaseTest extends TestCase
{
    private StatusRepositoryContract&MockObject $repository;
    private GetStatusesUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StatusRepositoryContract::class);
        $this->useCase    = new GetStatusesUseCase($this->repository);
    }

    private function makeStatus(string $name, string $code): ProductStatus
    {
        return ProductStatus::reconstitute(
            id: StatusId::generate(),
            name: new StatusName($name),
            code: new StatusCode($code),
            description: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    private function paginatedResult(array $statuses): array
    {
        return [
            'data'         => $statuses,
            'total'        => count($statuses),
            'per_page'     => 20,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function test_returns_paginated_list_with_default_filters(): void
    {
        $statuses = [
            $this->makeStatus('Activo', 'ACTIVO'),
            $this->makeStatus('Descontinuado', 'DESCONTINUADO'),
        ];

        $this->repository
            ->method('findAll')
            ->with($this->arrayHasKey('search'))
            ->willReturn($this->paginatedResult($statuses));

        $result = ($this->useCase)(new GetStatusesQuery());

        $this->assertCount(2, $result['data']);
        $this->assertContainsOnlyInstancesOf(StatusDTO::class, $result['data']);
    }

    public function test_filters_by_search_term_in_name(): void
    {
        $matching = [$this->makeStatus('Activo', 'ACTIVO')];

        $this->repository
            ->method('findAll')
            ->willReturn($this->paginatedResult($matching));

        $result = ($this->useCase)(new GetStatusesQuery(search: 'activo'));

        $this->assertCount(1, $result['data']);
        $this->assertSame('Activo', $result['data'][0]->name);
    }

    public function test_filters_by_search_term_in_code(): void
    {
        $matching = [$this->makeStatus('Descontinuado', 'DESCONTINUADO')];

        $this->repository
            ->method('findAll')
            ->willReturn($this->paginatedResult($matching));

        $result = ($this->useCase)(new GetStatusesQuery(search: 'DESCONT'));

        $this->assertCount(1, $result['data']);
        $this->assertSame('DESCONTINUADO', $result['data'][0]->code);
    }

    public function test_filters_by_is_active(): void
    {
        $active = [$this->makeStatus('Activo', 'ACTIVO')];

        $this->repository
            ->method('findAll')
            ->willReturn($this->paginatedResult($active));

        $result = ($this->useCase)(new GetStatusesQuery(isActive: true));

        $this->assertCount(1, $result['data']);
        $this->assertTrue($result['data'][0]->isActive);
    }

    public function test_returns_results_ordered_by_name_asc(): void
    {
        $statuses = [
            $this->makeStatus('Activo', 'ACTIVO'),
            $this->makeStatus('Descontinuado', 'DESCONTINUADO'),
            $this->makeStatus('Eliminado', 'ELIMINADO'),
        ];

        $this->repository
            ->method('findAll')
            ->willReturn($this->paginatedResult($statuses));

        $result = ($this->useCase)(new GetStatusesQuery());

        $this->assertSame('Activo', $result['data'][0]->name);
        $this->assertSame('Descontinuado', $result['data'][1]->name);
        $this->assertSame('Eliminado', $result['data'][2]->name);
    }

    public function test_returns_the_3_seeded_statuses_activo_descontinuado_eliminado(): void
    {
        $statuses = [
            $this->makeStatus('Activo', 'ACTIVO'),
            $this->makeStatus('Descontinuado', 'DESCONTINUADO'),
            $this->makeStatus('Eliminado', 'ELIMINADO'),
        ];

        $this->repository
            ->method('findAll')
            ->willReturn($this->paginatedResult($statuses));

        $result = ($this->useCase)(new GetStatusesQuery());

        $codes = array_map(fn ($d) => $d->code, $result['data']);
        $this->assertContains('ACTIVO', $codes);
        $this->assertContains('DESCONTINUADO', $codes);
        $this->assertContains('ELIMINADO', $codes);
    }
}
