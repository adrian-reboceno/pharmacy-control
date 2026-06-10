<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Application/UseCase/GetLaboratories/GetLaboratoriesUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Application\UseCase\GetLaboratories;

use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;

final class GetLaboratoriesUseCase
{
    public function __construct(
        private readonly LaboratoryRepositoryContract $repository,
    ) {}

    public function __invoke(GetLaboratoriesQuery $query): array
    {
        $perPage = min(max($query->perPage, 1), 100);

        $filters = [
            'per_page' => $perPage,
            'page' => max($query->page, 1),
        ];

        if ($query->search !== null) {
            $filters['search'] = $query->search;
        }
        if ($query->countryCode !== null) {
            $filters['country_code'] = $query->countryCode;
        }
        if ($query->isActive !== null) {
            $filters['is_active'] = $query->isActive;
        }

        $result = $this->repository->findAll($filters);

        return [
            'data' => array_map(
                static fn (Laboratory $lab) => LaboratoryDTO::fromDomain($lab),
                $result['data']
            ),
            'total' => $result['total'],
            'per_page' => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page' => $result['last_page'],
        ];
    }
}
