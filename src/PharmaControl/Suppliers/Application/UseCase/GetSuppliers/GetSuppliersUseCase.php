<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\UseCase\GetSuppliers;

use PharmaControl\Suppliers\Application\DTO\SupplierDTO;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;

final class GetSuppliersUseCase
{
    public function __construct(
        private readonly SupplierRepositoryContract $repository,
    ) {}

    public function __invoke(GetSuppliersQuery $query): array
    {
        $filters = [
            'per_page' => $query->perPage,
            'page' => $query->page,
        ];

        if ($query->search !== null) {
            $filters['search'] = $query->search;
        }
        if ($query->type !== null) {
            $filters['type'] = $query->type;
        }
        if ($query->isActive !== null) {
            $filters['is_active'] = $query->isActive;
        }

        $result = $this->repository->findAll($filters);

        return [
            'data' => array_map(fn ($s) => SupplierDTO::fromDomain($s), $result['data']),
            'total' => $result['total'],
            'per_page' => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page' => $result['last_page'],
        ];
    }
}
