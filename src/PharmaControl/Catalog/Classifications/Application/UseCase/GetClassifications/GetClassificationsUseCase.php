<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Application/UseCase/GetClassifications/GetClassificationsUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Application\UseCase\GetClassifications;

use PharmaControl\Catalog\Classifications\Application\DTO\ClassificationDTO;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;

final class GetClassificationsUseCase
{
    public function __construct(
        private readonly ClassificationRepositoryContract $repository,
    ) {}

    public function __invoke(GetClassificationsQuery $query): array
    {
        $perPage = min(max($query->perPage, 1), 100);

        $filters = [
            'per_page' => $perPage,
            'page' => max($query->page, 1),
        ];

        if ($query->isActive !== null) {
            $filters['is_active'] = $query->isActive;
        }
        if ($query->isControlled !== null) {
            $filters['is_controlled'] = $query->isControlled;
        }

        $result = $this->repository->findAll($filters);

        return [
            'data' => array_map(
                static fn (MedicationClassification $c) => ClassificationDTO::fromDomain($c),
                $result['data']
            ),
            'total' => $result['total'],
            'per_page' => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page' => $result['last_page'],
        ];
    }
}
