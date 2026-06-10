<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Laboratories/Application/UseCase/GetLaboratoriesUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Laboratories\Application\UseCase;

use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Application\UseCase\GetLaboratories\GetLaboratoriesQuery;
use PharmaControl\Catalog\Laboratories\Application\UseCase\GetLaboratories\GetLaboratoriesUseCase;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetLaboratoriesUseCaseTest extends TestCase
{
    private LaboratoryRepositoryContract&MockObject $repository;

    private GetLaboratoriesUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LaboratoryRepositoryContract::class);
        $this->useCase = new GetLaboratoriesUseCase($this->repository);
    }

    private function makePagedResult(array $labs = []): array
    {
        return [
            'data' => $labs,
            'total' => count($labs),
            'per_page' => 20,
            'current_page' => 1,
            'last_page' => 1,
        ];
    }

    private function makeLaboratory(string $name = 'Bayer', string $country = 'DE'): Laboratory
    {
        return Laboratory::create(
            LaboratoryId::generate(),
            new LaboratoryName($name),
            new CountryCode($country),
            null,
            null,
        );
    }

    public function test_returns_paginated_list_with_default_filters(): void
    {
        $labs = [$this->makeLaboratory('Bayer'), $this->makeLaboratory('Pfizer', 'US')];

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($this->makePagedResult($labs));

        $result = ($this->useCase)(new GetLaboratoriesQuery);

        self::assertCount(2, $result['data']);
        self::assertContainsOnlyInstancesOf(LaboratoryDTO::class, $result['data']);
        self::assertSame(2, $result['total']);
    }

    public function test_filters_by_search_term(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('search'))
            ->willReturn($this->makePagedResult());

        ($this->useCase)(new GetLaboratoriesQuery(search: 'Bayer'));
    }

    public function test_filters_by_country_code(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('country_code'))
            ->willReturn($this->makePagedResult());

        ($this->useCase)(new GetLaboratoriesQuery(countryCode: 'MX'));
    }

    public function test_filters_by_is_active_true(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('is_active'))
            ->willReturn($this->makePagedResult());

        ($this->useCase)(new GetLaboratoriesQuery(isActive: true));
    }

    public function test_caps_per_page_at_100(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn (array $f) => $f['per_page'] === 100))
            ->willReturn($this->makePagedResult());

        ($this->useCase)(new GetLaboratoriesQuery(perPage: 9999));
    }
}
