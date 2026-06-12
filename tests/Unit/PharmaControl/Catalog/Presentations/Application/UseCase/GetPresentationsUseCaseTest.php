<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Presentations\Application\UseCase;

use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Application\UseCase\GetPresentations\GetPresentationsQuery;
use PharmaControl\Catalog\Presentations\Application\UseCase\GetPresentations\GetPresentationsUseCase;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetPresentationsUseCaseTest extends TestCase
{
    private PresentationRepositoryContract&MockObject $repository;

    private GetPresentationsUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PresentationRepositoryContract::class);
        $this->useCase = new GetPresentationsUseCase($this->repository);
    }

    private function makePresentation(string $name, string $abbreviation): Presentation
    {
        return Presentation::create(
            PresentationId::generate(),
            new PresentationName($name),
            new Abbreviation($abbreviation),
            null,
            null,
        );
    }

    private function makePagedResult(array $presentations): array
    {
        return [
            'data' => $presentations,
            'total' => count($presentations),
            'per_page' => 20,
            'current_page' => 1,
            'last_page' => 1,
        ];
    }

    public function test_returns_paginated_list_with_default_filters(): void
    {
        $presentations = [
            $this->makePresentation('Cápsula', 'Cap'),
            $this->makePresentation('Tableta', 'Tab'),
        ];
        $this->repository->method('findAll')->willReturn($this->makePagedResult($presentations));

        $result = ($this->useCase)(new GetPresentationsQuery);

        self::assertCount(2, $result['data']);
        self::assertContainsOnlyInstancesOf(PresentationDTO::class, $result['data']);
        self::assertSame(2, $result['total']);
    }

    public function test_filters_by_search_term_in_name(): void
    {
        $tab = $this->makePresentation('Tableta', 'Tab');
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('search'))
            ->willReturn($this->makePagedResult([$tab]));

        $result = ($this->useCase)(new GetPresentationsQuery(search: 'Tab'));

        self::assertCount(1, $result['data']);
        self::assertSame('Tableta', $result['data'][0]->name);
    }

    public function test_filters_by_search_term_in_abbreviation(): void
    {
        $cap = $this->makePresentation('Cápsula', 'Cap');
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('search'))
            ->willReturn($this->makePagedResult([$cap]));

        $result = ($this->useCase)(new GetPresentationsQuery(search: 'Cap'));

        self::assertCount(1, $result['data']);
        self::assertSame('Cap', $result['data'][0]->abbreviation);
    }

    public function test_filters_by_is_active(): void
    {
        $active = $this->makePresentation('Tableta', 'Tab');
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('is_active'))
            ->willReturn($this->makePagedResult([$active]));

        $result = ($this->useCase)(new GetPresentationsQuery(isActive: true));

        self::assertCount(1, $result['data']);
        self::assertTrue($result['data'][0]->isActive);
    }

    public function test_returns_results_ordered_by_name_asc(): void
    {
        $cap = $this->makePresentation('Cápsula', 'Cap');
        $tab = $this->makePresentation('Tableta', 'Tab');

        $this->repository->method('findAll')->willReturn($this->makePagedResult([$cap, $tab]));

        $result = ($this->useCase)(new GetPresentationsQuery);

        self::assertSame('Cápsula', $result['data'][0]->name);
        self::assertSame('Tableta', $result['data'][1]->name);
    }
}
