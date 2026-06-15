<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Application\UseCase\GetStatuses;

use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;

final class GetStatusesUseCase
{
    public function __construct(
        private readonly StatusRepositoryContract $repository,
    ) {}

    public function __invoke(GetStatusesQuery $query): array
    {
        $result = $this->repository->findAll([
            'search'    => $query->search,
            'is_active' => $query->isActive,
            'per_page'  => $query->perPage,
            'page'      => $query->page,
        ]);

        return [
            'data'         => array_map(
                fn ($status) => StatusDTO::fromDomain($status),
                $result['data']
            ),
            'total'        => $result['total'],
            'per_page'     => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page'    => $result['last_page'],
        ];
    }
}
