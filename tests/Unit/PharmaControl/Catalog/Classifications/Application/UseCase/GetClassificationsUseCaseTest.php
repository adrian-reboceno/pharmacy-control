<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Classifications/Application/UseCase/GetClassificationsUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Classifications\Application\UseCase;

use PharmaControl\Catalog\Classifications\Application\DTO\ClassificationDTO;
use PharmaControl\Catalog\Classifications\Application\UseCase\GetClassifications\GetClassificationsQuery;
use PharmaControl\Catalog\Classifications\Application\UseCase\GetClassifications\GetClassificationsUseCase;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\PrescriptionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetClassificationsUseCaseTest extends TestCase
{
    private ClassificationRepositoryContract&MockObject $repository;

    private GetClassificationsUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ClassificationRepositoryContract::class);
        $this->useCase = new GetClassificationsUseCase($this->repository);
    }

    private function makeClassification(LgsGroup $group, PrescriptionType $type): MedicationClassification
    {
        return MedicationClassification::create(
            id: ClassificationId::generate(),
            lgsGroup: $group,
            name: new ClassificationName($group->label()),
            prescriptionType: $type,
            validityDays: null,
            validityNote: null,
            createdBy: null,
        );
    }

    private function makePaginatedResult(array $items): array
    {
        return [
            'data' => $items,
            'total' => count($items),
            'per_page' => 20,
            'current_page' => 1,
            'last_page' => 1,
        ];
    }

    public function test_returns_all_7_classifications_with_default_filters(): void
    {
        $groups = [
            [LgsGroup::I,    PrescriptionType::CON_CODIGO_BARRAS],
            [LgsGroup::II,   PrescriptionType::NORMAL],
            [LgsGroup::III,  PrescriptionType::NORMAL],
            [LgsGroup::IV_A, PrescriptionType::NORMAL],
            [LgsGroup::IV_B, PrescriptionType::NORMAL],
            [LgsGroup::V,    PrescriptionType::SIN_RECETA],
            [LgsGroup::VI,   PrescriptionType::SIN_RECETA],
        ];
        $items = array_map(fn ($g) => $this->makeClassification($g[0], $g[1]), $groups);

        $this->repository->method('findAll')
            ->willReturn($this->makePaginatedResult($items));

        $result = ($this->useCase)(new GetClassificationsQuery);

        self::assertCount(7, $result['data']);
        self::assertContainsOnlyInstancesOf(ClassificationDTO::class, $result['data']);
    }

    public function test_filters_by_is_active(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->arrayHasKey('is_active'))
            ->willReturn($this->makePaginatedResult([]));

        ($this->useCase)(new GetClassificationsQuery(isActive: true));
    }

    public function test_filters_by_is_controlled_true(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($f) => ($f['is_controlled'] ?? null) === true))
            ->willReturn($this->makePaginatedResult([]));

        ($this->useCase)(new GetClassificationsQuery(isControlled: true));
    }

    public function test_filters_by_is_controlled_false(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn ($f) => ($f['is_controlled'] ?? null) === false))
            ->willReturn($this->makePaginatedResult([]));

        ($this->useCase)(new GetClassificationsQuery(isControlled: false));
    }
}
