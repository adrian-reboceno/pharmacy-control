<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\GetRoutes;

use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;

final class GetRoutesUseCase
{
    public function __construct(
        private readonly RouteRepositoryContract $repository,
    ) {}

    public function __invoke(GetRoutesQuery $query): array
    {
        $filters = [];

        if ($query->search !== null) {
            $filters['search'] = $query->search;
        }
        if ($query->isActive !== null) {
            $filters['is_active'] = $query->isActive;
        }

        $filters['per_page'] = $query->perPage;
        $filters['page'] = $query->page;

        $result = $this->repository->findAll($filters);

        return [
            ...$result,
            'data' => array_map(
                fn ($route) => RouteDTO::fromDomain($route),
                $result['data']
            ),
        ];
    }
}
